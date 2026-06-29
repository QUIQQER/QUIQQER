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
            'https://example.test/var/update/runs/',
            '/usr/bin/php'
        );

        $launch = $launcher->create(1000);
        $run = $launch->getRun();

        $this->assertSame(
            'https://example.test/var/update/runs/' . $run->getState()->getId()
            . '/execute.php?token=' . $run->getToken(),
            $launch->getWebUrl()
        );

        $this->assertSame(
            "'/usr/bin/php' '" . $run->getExecuteFile() . "' '" . $run->getToken() . "'",
            $launch->getCliCommand()
        );
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
