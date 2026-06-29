<?php

namespace QUI\System\Update;

use PHPUnit\Framework\TestCase;

class RunLauncherFactoryTest extends TestCase
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

    public function testCreateUsesVarAndUrlVarDirectory(): void
    {
        $launcher = RunLauncherFactory::create(
            $this->root . '/var/',
            'https://example.test/var/',
            '/usr/bin/php',
            600
        );

        $launch = $launcher->create(1000);

        $this->assertStringStartsWith(
            $this->root . '/var/update/runs/',
            $launch->getRun()->getDirectory()
        );

        $this->assertStringStartsWith(
            'https://example.test/var/update/runs/',
            $launch->getWebUrl()
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
