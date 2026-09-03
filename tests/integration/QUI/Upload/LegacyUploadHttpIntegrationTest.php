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
use const CURLOPT_POST;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_TIMEOUT;

/**
 * Exercises PHP's real multipart upload handling and Manager::formUpload().
 *
 * The permission boundary and ordering in the legacy entry point itself are
 * covered by LegacyUploadEndpointTest without requiring a second installed
 * QUIQQER bootstrap in the HTTP server process.
 */
class LegacyUploadHttpIntegrationTest extends TestCase
{
    private string $testDirectory;

    private string $varDirectory;

    private string $markerFile;

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
        $this->serverLog = $this->testDirectory . '/server.log';

        mkdir($this->varDirectory, 0777, true);

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
        $Curl = curl_init('http://127.0.0.1:' . $this->serverPort . '/index.php?mode=' . $mode);

        if ($Curl === false) {
            throw new RuntimeException('Could not initialize cURL.');
        }

        curl_setopt_array($Curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                'files' => new CURLFile($source, 'text/plain', 'legacy-http.txt')
            ],
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

    private function writeHttpEndpointWrapper(): void
    {
        $wrapper = <<<'PHP'
<?php

if (isset($_GET['health'])) {
    http_response_code(204);
    exit;
}

require %AUTOLOAD_FILE%;

if (($_GET['mode'] ?? '') !== 'allowed') {
    http_response_code(403);
    exit;
}

class LegacyHttpUploadManager extends QUI\Upload\Manager
{
    public function __construct(private readonly string $testRoot)
    {
    }

    public function getDir(): string
    {
        return $this->testRoot . 'uploads/';
    }

    protected function getUserUploadDir(null | QUI\Interfaces\Users\User $User = null): string
    {
        return $this->getDir() . 'legacy-http-user/';
    }

    public function run(callable $onfinish): void
    {
        $this->formUpload($onfinish, [], '', '');
    }
}

$destination = %VAR_DIRECTORY% . 'uploads/legacy-http-user/legacy-http.txt';
$marker = %MARKER_FILE%;

$Manager = new LegacyHttpUploadManager(%VAR_DIRECTORY%);
$Manager->run(static function () use ($destination, $marker): void {
    file_put_contents($marker, json_encode([
        'destinationExists' => is_file($destination),
        'contents' => is_file($destination) ? file_get_contents($destination) : null
    ]));
});

http_response_code(200);
PHP;

        $wrapper = str_replace(
            ['%AUTOLOAD_FILE%', '%VAR_DIRECTORY%', '%MARKER_FILE%'],
            [
                var_export(CMS_DIR . 'packages/autoload.php', true),
                var_export($this->varDirectory, true),
                var_export($this->markerFile, true)
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
