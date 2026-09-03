<?php

namespace QUI\Upload;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\Users\Manager as UserManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function bin2hex;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function link;
use function ltrim;
use function mkdir;
use function random_bytes;
use function rawurldecode;
use function rmdir;
use function str_replace;
use function str_repeat;
use function substr;
use function symlink;
use function sys_get_temp_dir;
use function trim;
use function unlink;
use function var_export;

use const DIRECTORY_SEPARATOR;

require_once __DIR__ . '/TestUploadManager.php';

class ManagerTest extends TestCase
{
    private string $temporaryDirectory;
    private TestUploadManager $Manager;
    private ?UserManager $originalUsers;

    protected function setUp(): void
    {
        $this->originalUsers = QUI::$Users;
        $this->temporaryDirectory = sys_get_temp_dir() . '/quiqqer-upload-' . bin2hex(random_bytes(8));
        mkdir($this->temporaryDirectory, 0700, true);
        $this->Manager = new TestUploadManager($this->temporaryDirectory);

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn('test-user');

        $Users = $this->createMock(UserManager::class);
        $Users->method('getUserBySession')->willReturn($User);
        QUI::$Users = $Users;

        $_FILES = [];
        $_REQUEST = [];
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        $_REQUEST = [];
        QUI::$Users = $this->originalUsers;

        if (!is_dir($this->temporaryDirectory)) {
            return;
        }

        $Iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->temporaryDirectory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($Iterator as $Item) {
            if ($Item->isLink() || $Item->isFile()) {
                unlink($Item->getPathname());
                continue;
            }

            rmdir($Item->getPathname());
        }

        rmdir($this->temporaryDirectory);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidFilenameProvider(): array
    {
        return [
            'unix traversal' => ['../outside.txt'],
            'windows traversal' => ['..\\outside.txt'],
            'absolute unix path' => ['/tmp/outside.txt'],
            'windows drive path' => ['C:\\temp\\outside.txt'],
            'windows drive relative path' => ['C:outside.txt'],
            'UNC path' => ['\\\\server\\share\\outside.txt'],
            'NUL byte' => ["file\0.txt"],
            'line feed' => ["file\n.txt"],
            'tab' => ["file\t.txt"],
            'delete control character' => ["file\x7f.txt"],
            'empty' => [''],
            'spaces only' => ['   '],
            'single dot' => ['.'],
            'double dot' => ['..'],
            'dots after normalization' => [' ... '],
            'array value' => [['outside.txt']],
            'null value' => [null],
            'integer value' => [123],
            'decoded unix traversal' => [rawurldecode('%2e%2e%2foutside.txt')],
            'decoded windows traversal' => [rawurldecode('%2e%2e%5coutside.txt')],
            'mixed traversal' => [rawurldecode('folder%2f..%5coutside.txt')]
        ];
    }

    #[DataProvider('invalidFilenameProvider')]
    public function testInvalidFilenamesAreRejected(mixed $filename): void
    {
        $this->expectException(QUI\Exception::class);
        $this->Manager->validateUploadFilename($filename);
    }

    public function testValidUnicodeWhitespaceAndMultiDotNamesRemainSupported(): void
    {
        self::assertSame(
            'résumé.final.v2.tar.gz',
            $this->Manager->validateUploadFilename('  résumé.final.v2.tar.gz  ')
        );
        self::assertSame(
            'archive.part.01.tar.gz',
            $this->Manager->validateUploadFilename('archive.part.01.tar.gz')
        );
        self::assertSame(
            'file..part.txt',
            $this->Manager->validateUploadFilename('file..part.txt')
        );
    }

    public function testFilenameLengthIncludesMetadataSuffix(): void
    {
        self::assertSame(
            str_repeat('a', 250),
            $this->Manager->validateUploadFilename(str_repeat('a', 250))
        );

        $this->expectException(QUI\Exception::class);
        $this->Manager->validateUploadFilename(str_repeat('a', 251));
    }

    public function testNonScalarRequestFilenameIsRejectedByUploadEntryPoint(): void
    {
        $_REQUEST['filename'] = ['outside.txt'];

        $this->expectException(QUI\Exception::class);
        $this->Manager->upload();
    }

    public function testCallbackTraversalCannotIncludePhpOutsideHandlerRoot(): void
    {
        $payloadFile = $this->temporaryDirectory . '/callback-payload.php';
        $markerFile = $this->temporaryDirectory . '/callback-executed';
        $payload = '<?php file_put_contents('
            . var_export($markerFile, true)
            . ", 'executed');";
        file_put_contents($payloadFile, $payload);

        $callbackRoot = trim(OPT_DIR . 'quiqqer/core/admin/ajax', DIRECTORY_SEPARATOR);
        $rootLevels = count(explode(DIRECTORY_SEPARATOR, $callbackRoot));
        $relativePayload = str_repeat('..' . DIRECTORY_SEPARATOR, $rootLevels)
            . ltrim(substr($payloadFile, 0, -4), DIRECTORY_SEPARATOR);
        $callback = 'ajax_' . str_replace(DIRECTORY_SEPARATOR, '_', $relativePayload);

        try {
            $this->Manager->runCallback($callback);
            self::fail('Upload callback traversal was accepted.');
        } catch (QUI\Exception $Exception) {
            self::assertSame(404, $Exception->getCode());
        }

        self::assertFileDoesNotExist($markerFile);
    }

    public function testResolvedDataAndMetadataPathsAreDirectChildren(): void
    {
        $paths = $this->Manager->paths('report.final.txt');

        self::assertSame($paths['directory'], dirname($paths['file']) . DIRECTORY_SEPARATOR);
        self::assertSame($paths['directory'], dirname($paths['metadata']) . DIRECTORY_SEPARATOR);
        self::assertSame($paths['file'] . '.json', $paths['metadata']);
    }

    public function testMultipleChunksStayInsideUserUploadDirectory(): void
    {
        $this->Manager->addMetadata('chunked.txt', ['upload' => true]);
        $this->Manager->append('chunked.txt', 'first-');
        $this->Manager->append('chunked.txt', 'second');

        $paths = $this->Manager->paths('chunked.txt');
        self::assertSame('first-second', file_get_contents($paths['file']));
        self::assertSame('chunked.txt', $this->Manager->readMetadata('chunked.txt')->getAttribute('file'));
        self::assertFileExists($paths['metadata']);
    }

    public function testTraversalCannotDeleteOutsideFileOrMetadata(): void
    {
        $outsideFile = $this->temporaryDirectory . '/outside.txt';
        $outsideMetadata = $outsideFile . '.json';
        file_put_contents($outsideFile, 'outside');
        file_put_contents($outsideMetadata, 'metadata');

        try {
            $this->Manager->cancel('../outside.txt');
            self::fail('Traversal filename was accepted by cancel().');
        } catch (QUI\Exception) {
        }

        self::assertSame('outside', file_get_contents($outsideFile));
        self::assertSame('metadata', file_get_contents($outsideMetadata));
    }

    public function testTraversalCannotCreateOrReadOutsideMetadata(): void
    {
        $outsideMetadata = $this->temporaryDirectory . '/outside.txt.json';

        try {
            $this->Manager->addMetadata('../outside.txt', []);
            self::fail('Traversal filename was accepted by add().');
        } catch (QUI\Exception) {
        }

        self::assertFileDoesNotExist($outsideMetadata);
        file_put_contents($outsideMetadata, '{"file":"outside.txt"}');

        try {
            $this->Manager->readMetadata('../outside.txt');
            self::fail('Traversal filename was accepted by getFileData().');
        } catch (QUI\Exception) {
        }

        self::assertFileExists($outsideMetadata);
    }

    public function testExistingSymlinkUploadTargetIsNotFollowed(): void
    {
        $paths = $this->Manager->paths('link.txt');
        $outsideFile = $this->temporaryDirectory . '/outside.txt';
        file_put_contents($outsideFile, 'outside');
        symlink($outsideFile, $paths['file']);

        try {
            $this->Manager->append('link.txt', 'changed');
            self::fail('Symlink upload target was followed.');
        } catch (QUI\Exception) {
        }

        self::assertSame('outside', file_get_contents($outsideFile));
    }

    public function testExistingSymlinkMetadataTargetIsNotFollowed(): void
    {
        $paths = $this->Manager->paths('metadata.txt');
        $outsideFile = $this->temporaryDirectory . '/outside.json';
        file_put_contents($outsideFile, 'outside');
        symlink($outsideFile, $paths['metadata']);

        try {
            $this->Manager->addMetadata('metadata.txt', []);
            self::fail('Symlink metadata target was followed.');
        } catch (QUI\Exception) {
        }

        self::assertSame('outside', file_get_contents($outsideFile));
    }

    public function testHardLinkedUploadTargetIsRejectedBeforeWriting(): void
    {
        $paths = $this->Manager->paths('hardlink.txt');
        $outsideFile = $this->temporaryDirectory . '/outside.txt';
        file_put_contents($outsideFile, 'outside');
        link($outsideFile, $paths['file']);

        try {
            $this->Manager->append('hardlink.txt', 'changed');
            self::fail('Hard-linked upload target was written.');
        } catch (QUI\Exception) {
        }

        self::assertSame('outside', file_get_contents($outsideFile));
    }

    public function testSymlinkUserDirectoryIsRejected(): void
    {
        $this->Manager->paths('initial.txt');
        $userDirectory = $this->Manager->userDirectory();
        rmdir($userDirectory);

        $outsideDirectory = $this->temporaryDirectory . '/outside';
        mkdir($outsideDirectory);
        symlink($outsideDirectory, $userDirectory);

        $this->expectException(QUI\Exception::class);
        $this->Manager->paths('outside.txt');
    }

    public function testDirectoryAsUploadTargetIsRejected(): void
    {
        $paths = $this->Manager->paths('directory.txt');
        mkdir($paths['file']);

        $this->expectException(QUI\Exception::class);
        $this->Manager->paths('directory.txt');
    }

    public function testExtensionCheckUsesValidatedFilename(): void
    {
        $this->Manager->validateAndCheckAllowed('photo.final.jpg', 'image/jpeg', 'image/*', '*.jpg');
        self::assertTrue(true);

        try {
            $this->Manager->validateAndCheckAllowed(
                'image.jpg/../../file.php',
                'image/jpeg',
                'image/*',
                '*.jpg'
            );
            self::fail('Traversal filename reached the extension check.');
        } catch (QUI\Exception) {
        }

        $this->expectException(QUI\Exception::class);
        $this->Manager->validateAndCheckAllowed('shell.php', 'image/jpeg', 'image/*', '*.jpg');
    }

    public function testSingleFormUploadUsesCentralFilenameValidation(): void
    {
        $_FILES = [
            'files' => [
                'error' => UPLOAD_ERR_OK,
                'name' => '../outside.txt',
                'type' => 'text/plain',
                'tmp_name' => $this->temporaryDirectory . '/temporary-upload'
            ]
        ];

        $this->expectException(QUI\Exception::class);
        $this->Manager->runFormUpload('text/*', '*.txt');
    }

    public function testMultipleFormUploadUsesCentralFilenameValidation(): void
    {
        $_FILES = [
            'files' => [
                'error' => [UPLOAD_ERR_OK],
                'name' => [['outside.txt']],
                'type' => ['text/plain'],
                'tmp_name' => [$this->temporaryDirectory . '/temporary-upload']
            ]
        ];

        $this->expectException(QUI\Exception::class);
        $this->Manager->runFormUpload('text/*', '*.txt');
    }

    public function testFormUploadChecksExtensionAfterFilenameValidation(): void
    {
        $_FILES = [
            'files' => [
                'error' => UPLOAD_ERR_OK,
                'name' => 'shell.php',
                'type' => 'text/plain',
                'tmp_name' => $this->temporaryDirectory . '/temporary-upload'
            ]
        ];

        $this->expectException(QUI\Exception::class);
        $this->Manager->runFormUpload('text/*', '*.txt');
    }
}
