<?php

namespace QUI\Upload;

use CURLFile;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

use function bin2hex;
use function curl_close;
use function curl_exec;
use function curl_getinfo;
use function curl_init;
use function curl_setopt_array;
use function fclose;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_resource;
use function json_decode;
use function mkdir;
use function proc_close;
use function proc_get_status;
use function proc_open;
use function proc_terminate;
use function random_bytes;
use function rmdir;
use function stream_socket_get_name;
use function stream_socket_server;
use function str_replace;
use function strrpos;
use function substr;
use function sys_get_temp_dir;
use function unlink;
use function usleep;

use const CURLINFO_RESPONSE_CODE;
use const CURLOPT_COOKIEFILE;
use const CURLOPT_COOKIEJAR;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;

class LegacyUploadHttpIntegrationTest extends TestCase
{
    private string $testDirectory;

    private string $varDirectory;

    private string $markerFile;

    private string $cookieFile;

    private string $serverLog;

    private int $serverPort;

    /** @var resource|null */
    private mixed $serverProcess = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDirectory = sys_get_temp_dir() . '/quiqqer-legacy-upload-http-'
            . bin2hex(random_bytes(8));
        $this->varDirectory = $this->testDirectory . '/var/';
        $this->markerFile = $this->testDirectory . '/upload-marker.json';
        $this->cookieFile = $this->testDirectory . '/cookies.txt';
        $this->serverLog = $this->testDirectory . '/server.log';

        mkdir($this->varDirectory . 'sessions', 0777, true);
        mkdir($this->varDirectory . 'logs', 0777, true);

        $this->writeHttpEndpointWrapper();
        $this->serverPort = $this->reserveServerPort();
        $this->startHttpServer();
    }

    protected function tearDown(): void
    {
        $this->stopHttpServer();
        $this->removeTestDirectory();

        parent::tearDown();
    }

    public function testAuthorizedMultipartUploadUsesMoveUploadedFile(): void
    {
        $source = $this->testDirectory . '/source.txt';
        $contents = 'legacy HTTP upload ' . bin2hex(random_bytes(8));
        file_put_contents($source, $contents);

        $response = $this->sendMultipartUpload('allowed', $source);

        self::assertSame(200, $response['status'], $response['body'] . $this->getServerLog());
        self::assertFileExists($this->markerFile);

        $marker = json_decode((string)file_get_contents($this->markerFile), true);
        self::assertIsArray($marker);
        self::assertTrue($marker['destinationExists'] ?? false);
        self::assertSame($contents, $marker['contents'] ?? null);

        self::assertFileDoesNotExist(
            $this->varDirectory . 'uploads/legacy-http-user/legacy-http.txt',
            'The callback runs after move_uploaded_file(); the legacy flow must clean up afterwards.'
        );
    }

    public function testDeniedMultipartUploadStopsBeforeMoveUploadedFile(): void
    {
        $source = $this->testDirectory . '/denied-source.txt';
        file_put_contents($source, 'must not be moved');

        $response = $this->sendMultipartUpload('denied', $source);

        self::assertGreaterThanOrEqual(400, $response['status'], $response['body'] . $this->getServerLog());
        self::assertFileDoesNotExist($this->markerFile);
        self::assertDirectoryDoesNotExist(
            $this->varDirectory . 'uploads/legacy-http-user'
        );
    }

    /**
     * @return array{status: int, body: string}
     */
    private function sendMultipartUpload(string $mode, string $source): array
    {
        $securityHeaders = $this->getSecurityHeaders();
        $Curl = curl_init('http://127.0.0.1:' . $this->serverPort . '/index.php?mode=' . $mode);

        if ($Curl === false) {
            throw new RuntimeException('Could not initialize cURL.');
        }

        curl_setopt_array($Curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'files' => new CURLFile($source, 'text/plain', 'legacy-http.txt')
            ],
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_HTTPHEADER => $securityHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $body = curl_exec($Curl);
        $status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
        curl_close($Curl);

        if ($body === false) {
            throw new RuntimeException('The legacy upload HTTP request failed.');
        }

        return [
            'status' => $status,
            'body' => $body
        ];
    }

    /**
     * @return list<string>
     */
    private function getSecurityHeaders(): array
    {
        $Curl = curl_init('http://127.0.0.1:' . $this->serverPort . '/index.php?security=1');

        if ($Curl === false) {
            throw new RuntimeException('Could not initialize the security token request.');
        }

        curl_setopt_array($Curl, [
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20
        ]);

        $body = curl_exec($Curl);
        $status = curl_getinfo($Curl, CURLINFO_RESPONSE_CODE);
        curl_close($Curl);

        if ($body === false || $status !== 200) {
            throw new RuntimeException(
                'Could not obtain the HTTP upload security tokens.' . $this->getServerLog()
            );
        }

        $tokens = json_decode($body, true);

        if (
            !is_array($tokens)
            || !is_string($tokens['csrfIndex'] ?? null)
            || !is_string($tokens['csrfToken'] ?? null)
            || !is_string($tokens['jwtToken'] ?? null)
        ) {
            throw new RuntimeException('The HTTP upload security token response is invalid.');
        }

        return [
            'X-CSRF-Index: ' . $tokens['csrfIndex'],
            'X-CSRF-Token: ' . $tokens['csrfToken'],
            'X-JWT-Token: ' . $tokens['jwtToken']
        ];
    }

    private function writeHttpEndpointWrapper(): void
    {
        $wrapper = <<<'PHP'
<?php

if (isset($_GET['health'])) {
    http_response_code(204);
    exit;
}

define('QUIQQER_SYSTEM', true);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    define('QUIQQER_AJAX', true);
}

define('VAR_DIR', %VAR_DIRECTORY%);

require %BOOTSTRAP_FILE%;

$User = new class () extends QUI\Users\Nobody {
    public function getGroups(bool $array = true): array
    {
        return [];
    }
};

QUI::$Users = new class ($User) extends QUI\Users\Manager {
    private QUI\Interfaces\Users\User $User;

    public function __construct(QUI\Interfaces\Users\User $User)
    {
        $this->User = $User;
    }

    public function getUserBySession(): QUI\Interfaces\Users\User
    {
        return $this->User;
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

    public function getPermissions(mixed $Object): array
    {
        if (($_GET['mode'] ?? '') === 'allowed') {
            return ['quiqqer.frontend.upload' => true];
        }

        return [];
    }
};

QUI\Permissions\Permission::setUser($User);
QUI::getSession()->set('uuid', 'legacy-http-user');

if (isset($_GET['security'])) {
    $Csrf = new QUI\CSRF\AjaxCSRF();
    $csrfTokens = $Csrf->createTokenArray();

    header('Content-Type: application/json');
    echo json_encode([
        'csrfIndex' => $csrfTokens[$Csrf->getFormIndex()],
        'csrfToken' => $csrfTokens[$Csrf->getFormToken()],
        'jwtToken' => QUI\FRS\FrontendRequestSigning::createToke()
    ]);
    exit;
}

$destination = VAR_DIR . 'uploads/legacy-http-user/legacy-http.txt';
$marker = %MARKER_FILE%;

$_REQUEST['onfinish'] = static function () use ($destination, $marker): void {
    file_put_contents($marker, json_encode([
        'destinationExists' => is_file($destination),
        'contents' => is_file($destination) ? file_get_contents($destination) : null
    ]));
};

require %UPLOAD_ENDPOINT%;
PHP;

        $wrapper = str_replace(
            ['%VAR_DIRECTORY%', '%BOOTSTRAP_FILE%', '%MARKER_FILE%', '%UPLOAD_ENDPOINT%'],
            [
                var_export($this->varDirectory, true),
                var_export(CMS_DIR . 'bootstrap.php', true),
                var_export($this->markerFile, true),
                var_export(dirname(__DIR__, 4) . '/src/QUI/Upload/bin/upload.php', true)
            ],
            $wrapper
        );

        file_put_contents($this->testDirectory . '/index.php', $wrapper);
    }

    private function reserveServerPort(): int
    {
        $errno = 0;
        $error = '';
        $Socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);

        if ($Socket === false) {
            throw new RuntimeException('Could not reserve an HTTP server port: ' . $error);
        }

        $address = stream_socket_get_name($Socket, false);
        fclose($Socket);

        if ($address === false) {
            throw new RuntimeException('Could not determine the reserved HTTP server port.');
        }

        $separator = strrpos($address, ':');

        if ($separator === false) {
            throw new RuntimeException('Unexpected HTTP server address: ' . $address);
        }

        return (int)substr($address, $separator + 1);
    }

    private function startHttpServer(): void
    {
        $command = [
            PHP_BINARY,
            '-S',
            '127.0.0.1:' . $this->serverPort,
            '-t',
            $this->testDirectory
        ];
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['file', $this->serverLog, 'a'],
            2 => ['file', $this->serverLog, 'a']
        ];

        $this->serverProcess = proc_open($command, $descriptors, $pipes, $this->testDirectory);

        if (!is_resource($this->serverProcess)) {
            throw new RuntimeException('Could not start the PHP HTTP server.');
        }

        fclose($pipes[0]);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $Curl = curl_init('http://127.0.0.1:' . $this->serverPort . '/index.php?health=1');

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

        throw new RuntimeException('The PHP HTTP server did not become ready.' . $this->getServerLog());
    }

    private function stopHttpServer(): void
    {
        if (!is_resource($this->serverProcess)) {
            return;
        }

        proc_terminate($this->serverProcess);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $status = proc_get_status($this->serverProcess);

            if (!$status['running']) {
                break;
            }

            usleep(10_000);
        }

        proc_close($this->serverProcess);
        $this->serverProcess = null;
    }

    private function getServerLog(): string
    {
        if (!is_file($this->serverLog)) {
            return '';
        }

        return "\nPHP HTTP server log:\n" . file_get_contents($this->serverLog);
    }

    private function removeTestDirectory(): void
    {
        if (!is_dir($this->testDirectory)) {
            return;
        }

        $Iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->testDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($Iterator as $File) {
            if ($File->isDir() && !$File->isLink()) {
                rmdir($File->getPathname());
                continue;
            }

            unlink($File->getPathname());
        }

        rmdir($this->testDirectory);
    }
}
