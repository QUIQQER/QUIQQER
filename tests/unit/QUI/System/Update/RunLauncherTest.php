<?php

namespace QUI\System\Update;

use PHPUnit\Framework\TestCase;

class RunLauncherTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = '/tmp/quiqqer_update_runner_' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            $this->deleteDirectory($this->root);
        }
    }

    public function testCreateReturnsWebUrlAndCliCommand(): void
    {
        $repository = new RunRepository($this->root, 600);
        $launcher = new RunLauncher(
            $repository,
            'https://example.test/packages/quiqqer/core/bin/update-run.php',
            '/usr/bin/php'
        );

        $launch = $launcher->create(1000);
        $run = $launch->getRun();

        $this->assertSame(
            'https://example.test/packages/quiqqer/core/bin/update-run.php?id=' . $run->getState()->getId(),
            $launch->getWebUrl()
        );
        $this->assertNotSame('', $launch->getWebToken());
        $this->assertNotSame($run->getToken(), $launch->getWebToken());

        $this->assertSame(
            CliEnvironment::createShellPrefix()
            . "'/usr/bin/php' '" . $run->getExecuteFile() . "' '" . $run->getToken() . "'",
            $launch->getCliCommand()
        );

        $stateContents = (string)file_get_contents($run->getDirectory() . 'state.json');

        $this->assertStringNotContainsString($run->getToken(), $stateContents);
        $this->assertStringNotContainsString($launch->getWebToken(), $stateContents);
        $this->assertStringNotContainsString('cliCommand', $stateContents);
        $this->assertStringNotContainsString('token=', $stateContents);
    }

    private function deleteDirectory(string $directory): void
    {
        $items = new \FilesystemIterator($directory, \FilesystemIterator::SKIP_DOTS);

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
