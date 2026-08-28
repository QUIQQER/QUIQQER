<?php

namespace QUI\Projects;

use FilesystemIterator;
use QUI\Projects\Media;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function array_merge;
use function bin2hex;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function imagecolorallocate;
use function imagecreatetruecolor;
use function imagedestroy;
use function imagefilledrectangle;
use function imagepng;
use function is_dir;
use function is_file;
use function is_resource;
use function mkdir;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function random_bytes;
use function rmdir;
use function stream_socket_get_name;
use function stream_socket_server;
use function str_contains;
use function str_replace;
use function strrpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;
use function urlencode;
use function usleep;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_HEADERFUNCTION;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;

class ImageEndpointAccessTest extends ProjectIntegrationTestCase
{
    private const ALLOWED_USER_ID = 9001;

    private static string $testDirectory;

    private static string $varDirectory;

    private static string $serverLog;

    private static int $serverPort;

    /** @var resource|null */
    private static mixed $serverProcess = null;

    private static int $activeImageId;

    private static int $inactiveImageId;

    private static int $svgId;

    private static int $corruptImageId;

    private static int $folderId;

    /** @var list<int> */
    private static array $mediaIds = [];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$testDirectory = sys_get_temp_dir() . '/quiqqer-image-endpoint-'
            . bin2hex(random_bytes(8));
        self::$varDirectory = self::$testDirectory . '/var/';
        self::$serverLog = self::$testDirectory . '/server.log';

        mkdir(self::$varDirectory . 'sessions', 0777, true);
        mkdir(self::$varDirectory . 'logs', 0777, true);

        self::createMediaFixtures();
        self::writeHttpEndpointWrapper();
        self::$serverPort = self::reserveServerPort();
        self::startHttpServer();
    }

    public static function tearDownAfterClass(): void
    {
        self::stopHttpServer();
        self::deleteMediaFixtures();
        self::removeTestDirectory();

        parent::tearDownAfterClass();
    }

    public function testActivePublicImageIsDeliveredWithPublicCacheHeaders(): void
    {
        $Response = self::requestImage(self::$activeImageId);

        self::assertSame(200, $Response['status'], self::failureMessage($Response));
        self::assertNotSame('', $Response['body']);
        self::assertStringStartsWith('image/png', $Response['headers']['content-type'] ?? '');
        self::assertStringContainsString('public', $Response['headers']['cache-control'] ?? '');
    }

    public function testProtectedImageIsDeliveredToAuthorizedUserWithPrivateHeaders(): void
    {
        $Response = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'allowed'
        ]);

        self::assertSame(200, $Response['status'], self::failureMessage($Response));
        self::assertNotSame('', $Response['body']);
        self::assertStringContainsString('private', $Response['headers']['cache-control'] ?? '');
        self::assertStringContainsString('no-store', $Response['headers']['cache-control'] ?? '');
        self::assertStringNotContainsString('public', $Response['headers']['cache-control'] ?? '');
    }

    public function testProtectedImageReturnsUniformEmpty404ForNobody(): void
    {
        $Response = self::requestImage(self::$activeImageId, [
            '_access' => 'protected'
        ]);

        self::assertUniformNotFound($Response);
    }

    public function testInactiveImageReturns404AlthoughPhysicalFileExists(): void
    {
        $Image = self::getTestProject()->getMedia()->get(self::$inactiveImageId);
        self::assertFileExists($Image->getFullPath());

        self::assertUniformNotFound(self::requestImage(self::$inactiveImageId));
    }

    public function testProtectedSvgIsNotDelivered(): void
    {
        $Response = self::requestImage(self::$svgId, [
            '_access' => 'protected',
            '_user' => 'nobody',
            'quiadmin' => '1'
        ]);

        self::assertUniformNotFound($Response);
        self::assertStringNotContainsString('<svg', $Response['body']);
    }

    public function testExistingResizeCacheCannotBypassAcl(): void
    {
        $allowed = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'allowed',
            'maxwidth' => '37'
        ]);
        self::assertSame(200, $allowed['status'], self::failureMessage($allowed));

        $cacheFiles = self::getFilesBelow(self::$varDirectory . 'media/cache/permissions');
        self::assertNotSame([], $cacheFiles, 'The authorized request must create the protected resize cache.');

        $denied = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            'maxwidth' => '37'
        ]);
        self::assertUniformNotFound($denied);
    }

    public function testExistingAdminCacheCannotBypassAcl(): void
    {
        $allowed = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'backend-allowed',
            'quiadmin' => '1'
        ]);
        self::assertSame(200, $allowed['status'], self::failureMessage($allowed));

        $cacheFiles = self::getFilesBelow(self::$varDirectory . 'media/cache/admin');
        self::assertNotSame([], $cacheFiles, 'The authorized request must create the admin cache.');

        $denied = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'backend-denied',
            'quiadmin' => '1'
        ]);
        self::assertUniformNotFound($denied);
    }

    public function testFailedProtectedAdminResizeDoesNotReturnOriginal(): void
    {
        $Response = self::requestImage(self::$corruptImageId, [
            '_access' => 'protected',
            '_user' => 'backend-allowed',
            'quiadmin' => '1'
        ]);

        self::assertUniformNotFound($Response);
        self::assertStringNotContainsString('protected-original-secret', $Response['body']);
    }

    public function testQuiadminParameterDoesNotGrantAccess(): void
    {
        self::assertUniformNotFound(self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            'quiadmin' => '1'
        ]));
    }

    public function testForeignOrManipulatedRefererDoesNotGrantAccess(): void
    {
        self::assertUniformNotFound(self::requestImage(
            self::$activeImageId,
            ['_access' => 'protected'],
            ['Referer: https://attacker.invalid/admin/']
        ));

        self::assertUniformNotFound(self::requestImage(
            self::$activeImageId,
            ['_access' => 'protected'],
            ['Referer: http://127.0.0.1:' . self::$serverPort . '/admin/']
        ));
    }

    public function testBackendUserWithoutItemPermissionGetsNoPreview(): void
    {
        self::assertUniformNotFound(self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'backend-denied',
            'quiadmin' => '1'
        ]));
    }

    public function testAuthorizedBackendUserGetsPrivatePreview(): void
    {
        $Response = self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            '_user' => 'backend-allowed',
            'quiadmin' => '1'
        ]);

        self::assertSame(200, $Response['status'], self::failureMessage($Response));
        self::assertNotSame('', $Response['body']);
        self::assertStringContainsString('private', $Response['headers']['cache-control'] ?? '');
        self::assertStringNotContainsString('public', $Response['headers']['cache-control'] ?? '');
    }

    public function testProtectedFolderDoesNotReturnFolderIcon(): void
    {
        self::assertUniformNotFound(self::requestImage(self::$folderId, [
            '_access' => 'protected'
        ]));
    }

    public function testDeniedRequestDoesNotCreateResizeCache(): void
    {
        $before = self::getFilesBelow(self::$varDirectory . 'media/cache/permissions');

        self::assertUniformNotFound(self::requestImage(self::$activeImageId, [
            '_access' => 'protected',
            'maxwidth' => '83',
            'maxheight' => '61'
        ]));

        self::assertSame($before, self::getFilesBelow(self::$varDirectory . 'media/cache/permissions'));
    }

    public function testInvalidAndUnknownIdentifiersUseSame404Response(): void
    {
        $denied = self::requestImage(self::$activeImageId, ['_access' => 'protected']);
        $Requests = [
            ['id' => (string)self::$activeImageId],
            ['project' => self::getTestProjectName()],
            ['project' => 'unknown_project', 'id' => (string)self::$activeImageId],
            ['project' => 'invalid/project', 'id' => (string)self::$activeImageId],
            ['project' => self::getTestProjectName(), 'id' => '0'],
            ['project' => self::getTestProjectName(), 'id' => '-1'],
            ['project' => self::getTestProjectName(), 'id' => '1.5'],
            ['project' => self::getTestProjectName(), 'id' => '999999999'],
            ['project[]' => self::getTestProjectName(), 'id' => (string)self::$activeImageId],
            ['project' => self::getTestProjectName(), 'id[]' => (string)self::$activeImageId]
        ];

        foreach ($Requests as $params) {
            $Response = self::request($params);
            self::assertSame($denied['status'], $Response['status']);
            self::assertSame($denied['body'], $Response['body']);
            self::assertSame(
                $denied['headers']['cache-control'] ?? null,
                $Response['headers']['cache-control'] ?? null
            );
            self::assertArrayNotHasKey('content-disposition', $Response['headers']);
        }
    }

    public function testDeniedResponseHasNoSharedOrFileSpecificHeaders(): void
    {
        $Response = self::requestImage(self::$activeImageId, ['_access' => 'protected']);

        self::assertUniformNotFound($Response);
        self::assertStringNotContainsString('public', $Response['headers']['cache-control'] ?? '');
        self::assertArrayNotHasKey('content-type', $Response['headers']);
        self::assertArrayNotHasKey('content-disposition', $Response['headers']);
        self::assertArrayNotHasKey('last-modified', $Response['headers']);
    }

    /**
     * @param array<string, string> $params
     * @param list<string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private static function requestImage(int $id, array $params = [], array $headers = []): array
    {
        return self::request(array_merge([
            'project' => self::getTestProjectName(),
            'id' => (string)$id
        ], $params), $headers);
    }

    /**
     * @param array<string, string> $params
     * @param list<string> $headers
     * @return array{status: int, headers: array<string, string>, body: string}
     */
    private static function request(array $params, array $headers = []): array
    {
        $query = [];

        foreach ($params as $key => $value) {
            $query[] = urlencode($key) . '=' . urlencode($value);
        }

        $Curl = curl_init(
            'http://127.0.0.1:' . self::$serverPort . '/index.php?' . implode('&', $query)
        );

        if ($Curl === false) {
            throw new RuntimeException('Could not initialize image endpoint request.');
        }

        $responseHeaders = [];
        curl_setopt_array($Curl, [
            CURLOPT_HEADERFUNCTION => static function ($Curl, string $line) use (&$responseHeaders): int {
                $separator = strpos($line, ':');

                if ($separator !== false) {
                    $name = strtolower(trim(substr($line, 0, $separator)));
                    $responseHeaders[$name] = trim(substr($line, $separator + 1));
                }

                return strlen($line);
            },
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $body = curl_exec($Curl);
        $status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
        curl_close($Curl);

        if ($body === false) {
            throw new RuntimeException('Image endpoint request failed.' . self::getServerLog());
        }

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $body
        ];
    }

    /**
     * @param array{status: int, headers: array<string, string>, body: string} $Response
     */
    private static function assertUniformNotFound(array $Response): void
    {
        self::assertSame(404, $Response['status'], self::failureMessage($Response));
        self::assertSame('', $Response['body']);
        self::assertStringContainsString('private', $Response['headers']['cache-control'] ?? '');
        self::assertStringContainsString('no-store', $Response['headers']['cache-control'] ?? '');
        self::assertArrayNotHasKey('content-disposition', $Response['headers']);
    }

    /**
     * @param array{status: int, headers: array<string, string>, body: string} $Response
     */
    private static function failureMessage(array $Response): string
    {
        return $Response['body'] . self::getServerLog();
    }

    private static function createMediaFixtures(): void
    {
        $Project = self::getTestProject();
        $Media = $Project->getMedia();
        $Root = $Media->firstChild();
        $prefix = 'image-endpoint-' . bin2hex(random_bytes(4));
        $activePng = self::$testDirectory . '/' . $prefix . '-active.png';
        $inactivePng = self::$testDirectory . '/' . $prefix . '-inactive.png';
        $corruptPng = self::$testDirectory . '/' . $prefix . '-corrupt.png';
        $svg = self::$testDirectory . '/' . $prefix . '.svg';

        self::createPng($activePng, 96, 64);
        self::createPng($inactivePng, 80, 50);
        self::createPng($corruptPng, 72, 48);
        file_put_contents(
            $svg,
            '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"><rect width="20" height="20"/></svg>'
        );

        ProjectTestHelper::runAsSystemUser(
            static function () use ($Root, $activePng, $inactivePng, $corruptPng, $svg, $prefix): void {
                $Folder = $Root->createFolder($prefix . '-folder');
                $Folder->activate();
                self::$folderId = $Folder->getId();

                $Active = $Root->uploadFile($activePng);
                $Active->activate();
                self::$activeImageId = $Active->getId();

                $Inactive = $Root->uploadFile($inactivePng);
                self::$inactiveImageId = $Inactive->getId();

                $Svg = $Root->uploadFile($svg);
                $Svg->activate();
                self::$svgId = $Svg->getId();

                $Corrupt = $Root->uploadFile($corruptPng);
                $Corrupt->activate();
                self::$corruptImageId = $Corrupt->getId();
                file_put_contents($Corrupt->getFullPath(), 'protected-original-secret');
            }
        );

        self::$mediaIds = [
            self::$activeImageId,
            self::$inactiveImageId,
            self::$svgId,
            self::$corruptImageId
        ];
    }

    private static function deleteMediaFixtures(): void
    {
        if (!isset(self::$activeImageId)) {
            return;
        }

        $Media = self::getTestProject()->getMedia();

        ProjectTestHelper::runAsSystemUser(static function () use ($Media): void {
            foreach (self::$mediaIds as $id) {
                try {
                    $Item = $Media->get($id);
                    $Item->delete();
                    $Item->destroy();
                } catch (\Throwable) {
                }
            }

            try {
                $Folder = $Media->get(self::$folderId);
                $Folder->delete();
                $Folder->destroy();
            } catch (\Throwable) {
            }
        });
    }

    private static function createPng(string $file, int $width, int $height): void
    {
        $Image = imagecreatetruecolor($width, $height);
        $Color = imagecolorallocate($Image, 40, 120, 200);
        imagefilledrectangle($Image, 0, 0, $width - 1, $height - 1, $Color);
        imagepng($Image, $file);
        imagedestroy($Image);
    }

    private static function writeHttpEndpointWrapper(): void
    {
        $wrapper = <<<'PHP'
<?php

if (isset($_GET['health'])) {
    http_response_code(204);
    exit;
}

define('QUIQQER_SYSTEM', true);
define('VAR_DIR', %VAR_DIRECTORY%);

require %BOOTSTRAP_FILE%;

$userMode = is_string($_GET['_user'] ?? null) ? $_GET['_user'] : 'nobody';
$User = new class ($userMode) extends QUI\Users\Nobody {
    public function __construct(private string $mode)
    {
    }

    public function getUUID(): string | int
    {
        if (str_contains($this->mode, 'allowed')) {
            return %ALLOWED_USER_ID%;
        }

        return $this->mode === 'backend-denied' ? 9002 : '';
    }

    public function getId(): false | int
    {
        if (str_contains($this->mode, 'allowed')) {
            return %ALLOWED_USER_ID%;
        }

        return $this->mode === 'backend-denied' ? 9002 : false;
    }

    public function getGroups(bool $array = true): array
    {
        return [];
    }

    public function canUseBackend(): bool
    {
        return str_starts_with($this->mode, 'backend-');
    }
};

$Nobody = new class () extends QUI\Users\Nobody {
    public function getGroups(bool $array = true): array
    {
        return [];
    }
};

QUI::$Users = new class ($User, $Nobody) extends QUI\Users\Manager {
    public function __construct(
        private QUI\Interfaces\Users\User $User,
        private QUI\Users\Nobody $Nobody
    ) {
    }

    public function getUserBySession(): QUI\Interfaces\Users\User
    {
        return $this->User;
    }

    public function getNobody(): QUI\Users\Nobody
    {
        return $this->Nobody;
    }

    public function isSystemUser(mixed $User): bool
    {
        return false;
    }
};

QUI::$Rights = new class () extends QUI\Permissions\Manager {
    public function __construct()
    {
    }

    public function getMediaPermissions($MediaItem): array
    {
        return [
            'quiqqer.projects.media.view' => ($_GET['_access'] ?? '') === 'protected'
                ? 'u%ALLOWED_USER_ID%'
                : false
        ];
    }

    public function getPermissionData(string $permission): array
    {
        return ['type' => 'users_and_groups'];
    }
};

$MediaPermissions = new ReflectionProperty(QUI\Projects\Media::class, 'mediaPermissions');
$MediaPermissions->setValue(null, true);
QUI\Permissions\Permission::setUser($User);

chdir(%CORE_DIRECTORY%);
require %IMAGE_ENDPOINT%;
PHP;

        $wrapper = str_replace(
            [
                '%VAR_DIRECTORY%',
                '%BOOTSTRAP_FILE%',
                '%CORE_DIRECTORY%',
                '%IMAGE_ENDPOINT%',
                '%ALLOWED_USER_ID%'
            ],
            [
                var_export(self::$varDirectory, true),
                var_export(CMS_DIR . 'bootstrap.php', true),
                var_export(dirname(__DIR__, 4), true),
                var_export(dirname(__DIR__, 4) . '/image.php', true),
                (string)self::ALLOWED_USER_ID
            ],
            $wrapper
        );

        file_put_contents(self::$testDirectory . '/index.php', $wrapper);
    }

    private static function reserveServerPort(): int
    {
        $errno = 0;
        $error = '';
        $Socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

        if ($Socket === false) {
            throw new RuntimeException('Could not reserve image endpoint server port: ' . $error);
        }

        $address = stream_socket_get_name($Socket, false);
        fclose($Socket);

        if ($address === false || ($separator = strrpos($address, ':')) === false) {
            throw new RuntimeException('Could not determine image endpoint server port.');
        }

        return (int)substr($address, $separator + 1);
    }

    private static function startHttpServer(): void
    {
        $command = [
            PHP_BINARY,
            '-S',
            '127.0.0.1:' . self::$serverPort,
            '-t',
            self::$testDirectory
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', self::$serverLog, 'a'],
            2 => ['file', self::$serverLog, 'a']
        ];

        self::$serverProcess = proc_open($command, $descriptors, $pipes, self::$testDirectory);

        if (!is_resource(self::$serverProcess)) {
            throw new RuntimeException('Could not start image endpoint HTTP server.');
        }

        fclose($pipes[0]);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $Curl = curl_init('http://127.0.0.1:' . self::$serverPort . '/index.php?health=1');

            if ($Curl !== false) {
                curl_setopt_array($Curl, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 1
                ]);
                curl_exec($Curl);
                $status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
                curl_close($Curl);

                if ($status === 204) {
                    return;
                }
            }

            usleep(20_000);
        }

        throw new RuntimeException('Image endpoint HTTP server did not become ready.' . self::getServerLog());
    }

    private static function stopHttpServer(): void
    {
        if (!is_resource(self::$serverProcess)) {
            return;
        }

        proc_terminate(self::$serverProcess);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $status = proc_get_status(self::$serverProcess);

            if (!$status['running']) {
                break;
            }

            usleep(10_000);
        }

        proc_close(self::$serverProcess);
        self::$serverProcess = null;
    }

    /**
     * @return list<string>
     */
    private static function getFilesBelow(string $directory): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $files = [];
        $Iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($Iterator as $File) {
            if ($File->isFile()) {
                $files[] = $File->getPathname();
            }
        }

        sort($files);

        return $files;
    }

    private static function getServerLog(): string
    {
        return is_file(self::$serverLog)
            ? "\nImage endpoint HTTP server log:\n" . file_get_contents(self::$serverLog)
            : '';
    }

    private static function removeTestDirectory(): void
    {
        if (!is_dir(self::$testDirectory)) {
            return;
        }

        $Iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::$testDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($Iterator as $File) {
            if ($File->isDir() && !$File->isLink()) {
                rmdir($File->getPathname());
                continue;
            }

            unlink($File->getPathname());
        }

        rmdir(self::$testDirectory);
    }
}
