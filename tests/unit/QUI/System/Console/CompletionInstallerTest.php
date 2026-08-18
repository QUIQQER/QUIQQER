<?php

namespace QUI\System\Console;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\System\Console\Completion\Installer;

use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function substr_count;
use function uniqid;
use function unlink;

class CompletionInstallerTest extends TestCase
{
    private string $root;
    private string $homeDirectory;
    private string $configDirectory;

    protected function setUp(): void
    {
        $this->root = '/tmp/quiqqer_completion_' . uniqid('', true);
        $this->homeDirectory = $this->root . '/home';
        $this->configDirectory = $this->root . '/config';

        mkdir($this->homeDirectory, 0700, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $this->deleteDirectory($this->root);
        }
    }

    public function testBashInstallationIsIdempotentAndPreservesBashrc(): void
    {
        $bashrc = $this->homeDirectory . '/.bashrc';
        file_put_contents($bashrc, '# existing configuration');

        $Installer = $this->createInstaller();
        $result = $Installer->install('/bin/bash');
        $Installer->install('bash');

        self::assertSame($this->configDirectory . '/quiqqer/completion.bash', $result['completionFile']);
        self::assertSame($bashrc, $result['shellConfigFile']);

        $completion = file_get_contents($result['completionFile']);
        $configuration = file_get_contents($bashrc);

        self::assertIsString($completion);
        self::assertStringContainsString('"$executable" _complete', $completion);
        self::assertStringContainsString('colon_prefix="${current%:*}:"', $completion);
        self::assertStringContainsString('complete -F _quiqqer_console_completion console ./console', $completion);
        self::assertIsString($configuration);
        self::assertStringStartsWith('# existing configuration', $configuration);
        self::assertSame(1, substr_count($configuration, '# >>> QUIQQER console completion >>>'));
    }

    public function testZshInstallationUpdatesZshrc(): void
    {
        $result = $this->createInstaller()->install('zsh');

        self::assertSame($this->configDirectory . '/quiqqer/completion.zsh', $result['completionFile']);
        self::assertSame($this->homeDirectory . '/.zshrc', $result['shellConfigFile']);

        $completion = file_get_contents($result['completionFile']);

        self::assertIsString($completion);
        self::assertStringContainsString('compdef _quiqqer_console_completion console ./console', $completion);
    }

    public function testFishInstallationUsesAutoloadDirectory(): void
    {
        $result = $this->createInstaller()->install('fish');

        self::assertSame(
            $this->configDirectory . '/fish/completions/console.fish',
            $result['completionFile']
        );
        self::assertNull($result['shellConfigFile']);

        $completion = file_get_contents($result['completionFile']);

        self::assertIsString($completion);
        self::assertStringContainsString('commandline -opc', $completion);
        self::assertStringContainsString('complete --command console', $completion);
    }

    public function testUnsupportedShellIsRejected(): void
    {
        $this->expectException(Exception::class);

        $this->createInstaller()->install('ksh');
    }

    private function createInstaller(): Installer
    {
        return new Installer($this->homeDirectory, $this->configDirectory);
    }

    private function deleteDirectory(string $directory): void
    {
        $items = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);

        foreach ($items as $item) {
            if ($item->isDir()) {
                $this->deleteDirectory($item->getPathname());
                continue;
            }

            unlink($item->getPathname());
        }

        rmdir($directory);
    }
}
