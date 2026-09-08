<?php

namespace QUI\System\Update;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class RunWebSessionTest extends TestCase
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

    public function testExchangesOneTimeWebTokenForHashedSession(): void
    {
        $Repository = new RunRepository($this->root, 600);
        $Launcher = new RunLauncher($Repository, 'https://example.test/update-run.php', '/usr/bin/php');
        $Launch = $Launcher->create(1000);
        $runId = $Launch->getRun()->getState()->getId();
        $WebSession = new RunWebSession($Repository);

        $session = $WebSession->exchange($runId, $Launch->getWebToken(), 1001);
        $State = $WebSession->authenticate($runId, $session['token'], 1002);
        $persistedState = (string)file_get_contents($Launch->getRun()->getDirectory() . 'state.json');

        $this->assertSame($runId, $State->getId());
        $this->assertSame(1600, $session['expiresAt']);
        $this->assertStringNotContainsString($Launch->getWebToken(), $persistedState);
        $this->assertStringNotContainsString($session['token'], $persistedState);

        $State->assertToken($session['token']);
        $this->addToAssertionCount(1);
    }

    public function testWebTokenCanOnlyBeExchangedOnce(): void
    {
        $Repository = new RunRepository($this->root, 600);
        $Launcher = new RunLauncher($Repository, 'https://example.test/update-run.php', '/usr/bin/php');
        $Launch = $Launcher->create(1000);
        $runId = $Launch->getRun()->getState()->getId();
        $WebSession = new RunWebSession($Repository);

        $WebSession->exchange($runId, $Launch->getWebToken(), 1001);

        $this->expectException(InvalidArgumentException::class);
        $WebSession->exchange($runId, $Launch->getWebToken(), 1002);
    }

    public function testRejectsExpiredWebSession(): void
    {
        $Repository = new RunRepository($this->root, 600);
        $Launcher = new RunLauncher($Repository, 'https://example.test/update-run.php', '/usr/bin/php');
        $Launch = $Launcher->create(1000);
        $runId = $Launch->getRun()->getState()->getId();
        $WebSession = new RunWebSession($Repository);
        $session = $WebSession->exchange($runId, $Launch->getWebToken(), 1001);

        $this->expectException(InvalidArgumentException::class);
        $WebSession->authenticate($runId, $session['token'], 1601);
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
