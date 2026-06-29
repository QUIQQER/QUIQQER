<?php

namespace QUI\System\Update;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class RunRepositoryTest extends TestCase
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

    public function testCreateWritesRunFilesAndState(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $state = $run->getState();

        $this->assertDirectoryExists($run->getDirectory());
        $this->assertFileExists($run->getDirectory() . 'state.json');
        $this->assertFileExists($run->getExecuteFile());
        $this->assertSame(RunState::PHASE_CREATED, $state->getPhase());
        $this->assertSame(RunState::STATUS_CREATED, $state->getStatus());
        $this->assertNotSame($state->getId(), $run->getToken());
        $this->assertStringContainsString($state->getId(), (string)file_get_contents($run->getExecuteFile()));
    }

    public function testLoadReturnsPersistedState(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);

        $loaded = $repository->load($run->getState()->getId());

        $this->assertSame($run->getState()->getId(), $loaded->getId());
        $this->assertSame(RunState::PHASE_CREATED, $loaded->getPhase());
    }

    public function testRejectsInvalidRunIdentifier(): void
    {
        $repository = new RunRepository($this->root, 600);

        $this->expectException(InvalidArgumentException::class);

        $repository->load('../state');
    }

    public function testMissingStateThrowsException(): void
    {
        $repository = new RunRepository($this->root, 600);

        $this->expectException(RuntimeException::class);

        $repository->load(str_repeat('a', 32));
    }

    public function testLockRejectsSecondConcurrentLock(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $lock = $repository->acquireLock($run->getState()->getId());

        try {
            $this->expectException(RuntimeException::class);
            $repository->acquireLock($run->getState()->getId());
        } finally {
            $repository->releaseLock($lock);
        }
    }

    public function testDeleteRemovesRunDirectory(): void
    {
        $repository = new RunRepository($this->root, 600);
        $run = $repository->create(1000);
        $directory = $run->getDirectory();

        $repository->delete($run->getState()->getId());

        $this->assertDirectoryDoesNotExist($directory);
    }

    public function testDeleteExpiredRemovesOnlyExpiredRuns(): void
    {
        $repository = new RunRepository($this->root, 600);
        $expiredRun = $repository->create(1000);
        $activeRun = $repository->create(2000);

        $deleted = $repository->deleteExpired(1601);

        $this->assertSame([$expiredRun->getState()->getId()], $deleted);
        $this->assertDirectoryDoesNotExist($expiredRun->getDirectory());
        $this->assertDirectoryExists($activeRun->getDirectory());
    }

    public function testCleanupAndFindActiveDeletesOldRunsButKeepsFailedRuns(): void
    {
        $repository = new RunRepository($this->root, 600);
        $oldRun = $repository->create(1000);
        $freshRun = $repository->create(2000);
        $failedRun = $repository->create(500);
        $failedState = $failedRun->getState();
        $failedState->markFailed('failed for debugging', 501);
        $repository->save($failedState);

        $result = $repository->cleanupAndFindActive(2000, 864);

        $this->assertSame([$oldRun->getState()->getId()], $result['deleted']);
        $this->assertSame([$freshRun->getState()->getId()], array_map(
            static fn (RunState $state): string => $state->getId(),
            $result['active']
        ));
        $this->assertDirectoryDoesNotExist($oldRun->getDirectory());
        $this->assertDirectoryExists($freshRun->getDirectory());
        $this->assertDirectoryExists($failedRun->getDirectory());
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
