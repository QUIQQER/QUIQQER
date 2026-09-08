<?php

namespace QUI\Lock;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Lock\Store\FlockStore;

class EditingLocksTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/quiqqer-editing-' . bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
        Locker::setProcessLockStore(new FlockStore($this->directory . '/mutex'));
    }

    protected function tearDown(): void
    {
        Locker::setProcessLockStore(null);
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($this->directory);
    }

    private function store(string $backend): AdapterInterface
    {
        if ($backend === 'files') {
            $Store = new FilesystemAdapter('test', 0, $this->directory . '/records');
        } else {
            $Connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'path' => $this->directory . '/locks.db']);
            $Store = new EditingDbalAdapter($Connection, 'test');
        }
        $Store->setLogger(new StoreLogger());
        return $Store;
    }

    public static function backends(): array
    {
        return [['files'], ['dbal']];
    }

    #[DataProvider('backends')]
    public function testOwnershipPersistsAcrossInstancesAndTokensProtectAgainstStaleTabs(string $backend): void
    {
        $First = new EditingLocks($this->store($backend));
        $Second = new EditingLocks($this->store($backend));
        $one = str_repeat('a', 32);
        $two = str_repeat('b', 32);
        self::assertTrue($First->acquire('site:one', 'alice', $one));
        self::assertFalse($Second->acquire('site:one', 'bob', $two));
        self::assertTrue($Second->acquire('site:one', 'alice', $two), 'The same user may open another tab or reload.');
        self::assertTrue($Second->refresh('site:one', 'alice', $one));
        self::assertArrayNotHasKey('token', $Second->status('site:one'));
        self::assertArrayNotHasKey('tokens', $Second->status('site:one'));
        self::assertSame('saved', $Second->run('site:one', 'alice', $two, fn() => 'saved'));
        self::assertTrue($First->release('site:one', 'alice', $one));
        self::assertTrue($Second->refresh('site:one', 'alice', $two));
        self::assertFalse($First->acquire('site:one', 'bob', $one));
        self::assertFalse($First->release('site:one', 'alice', $one));
        self::assertFalse($First->refresh('site:one', 'alice', $one));
        self::assertSame('alice', $Second->status('site:one')['owner']);
        self::assertTrue($Second->release('site:one', 'alice', $two));
        self::assertTrue($First->acquire('site:one', 'bob', $one));
        self::assertTrue($First->acquire('site:two', 'bob', $one));
    }

    #[DataProvider('backends')]
    public function testAbandonedTabsExpireIndependentlyOfActiveTabs(string $backend): void
    {
        $Store = $this->store($backend);
        $expired = str_repeat('a', 32);
        $active = str_repeat('b', 32);
        $Item = $Store->getItem(hash('sha256', 'site:one'));
        $Item->set([
            'owner' => 'alice',
            'tokens' => [$expired => time() - 1, $active => time() + 60],
            'expires' => time() + 60
        ]);
        self::assertTrue($Store->save($Item));
        $Locks = new EditingLocks($this->store($backend));
        self::assertFalse($Locks->refresh('site:one', 'alice', $expired));
        self::assertFalse($Locks->release('site:one', 'alice', $expired));
        self::assertTrue($Locks->refresh('site:one', 'alice', $active));
        self::assertFalse($Locks->acquire('site:one', 'bob', $expired));
        self::assertTrue($Locks->release('site:one', 'alice', $active));
        self::assertNull($Locks->status('site:one'));
    }

    #[DataProvider('backends')]
    public function testExistingSingleEditorRecordAllowsAnotherEditorOfTheSameUser(string $backend): void
    {
        $Store = $this->store($backend);
        $old = str_repeat('a', 32);
        $new = str_repeat('b', 32);
        $Item = $Store->getItem(hash('sha256', 'site:one'));
        $Item->set(['owner' => 'alice', 'token' => $old, 'expires' => time() + 60]);
        self::assertTrue($Store->save($Item));
        $Locks = new EditingLocks($this->store($backend));
        self::assertTrue($Locks->acquire('site:one', 'alice', $new));
        self::assertTrue($Locks->refresh('site:one', 'alice', $old));
        self::assertTrue($Locks->release('site:one', 'alice', $old));
        self::assertTrue($Locks->refresh('site:one', 'alice', $new));
        self::assertTrue($Locks->release('site:one', 'alice', '', true));
        self::assertTrue($Locks->acquire('site:one', 'bob', $old));
        self::assertFalse($Locks->release('site:one', 'alice', $new));
        self::assertFalse($Locks->refresh('site:one', 'alice', $new));
        self::assertSame('bob', $Locks->status('site:one')['owner']);
    }

    #[DataProvider('backends')]
    public function testExpiredLeasesCanBeReplacedButNeverRenewedByTheirOldOwner(string $backend): void
    {
        $Store = $this->store($backend);
        $Item = $Store->getItem(hash('sha256', 'site:one'));
        $Item->set(['owner' => 'alice', 'token' => str_repeat('a', 32), 'expires' => time() - 1]);
        self::assertTrue($Store->save($Item));
        $Locks = new EditingLocks($this->store($backend));
        self::assertNull($Locks->status('site:one'));
        self::assertFalse($Locks->refresh('site:one', 'alice', str_repeat('a', 32)));
        self::assertTrue($Locks->acquire('site:one', 'bob', str_repeat('b', 32)));
    }

    #[DataProvider('backends')]
    public function testWritesRequireTheCurrentTokenAndReleaseTheirMutexOnFailure(string $backend): void
    {
        $Locks = new EditingLocks($this->store($backend));
        $one = str_repeat('a', 32);
        self::assertTrue($Locks->acquire('site:one', 'alice', $one));
        foreach ([['bob', $one], ['alice', str_repeat('b', 32)], ['bob', null]] as [$owner, $token]) {
            try {
                $Locks->run('site:one', $owner, $token, fn() => self::fail('Invalid owner must not write.'));
                self::fail('Ownership check must reject the write.');
            } catch (Exception $Exception) {
                self::assertSame(703, $Exception->getCode());
            }
        }
        self::assertSame('saved', $Locks->run('site:one', 'alice', $one, fn() => 'saved'));
        self::assertTrue($Locks->release('site:one', 'alice', $one));
        try {
            $Locks->run('site:one', 'alice', $one, fn() => self::fail('Expired editor must not write.'));
            self::fail('Missing lease must reject the editor.');
        } catch (Exception $Exception) {
            self::assertSame(703, $Exception->getCode());
        }
        self::assertSame('server-save', $Locks->run('site:one', 'alice', null, fn() => 'server-save'));
    }

    public function testStorageFailureDoesNotGrantOwnership(): void
    {
        $Store = $this->createMock(AdapterInterface::class);
        $Store->method('getItem')->willThrowException(new Exception('Unavailable', 503));
        $this->expectExceptionCode(503);
        (new EditingLocks($Store))->acquire('site:one', 'alice', str_repeat('a', 32));
    }

    public function testStorageWarningsAreErrorsWithoutLeakingCredentials(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Editing lock storage is unavailable.');
        (new StoreLogger())->warning('redis://secret@example.org');
    }

    public function testMalformedRecordDoesNotGrantOwnership(): void
    {
        $Store = new ArrayAdapter();
        $Store->save($Store->getItem(hash('sha256', 'site:one'))->set('bad-data'));
        $this->expectExceptionCode(503);
        (new EditingLocks($Store))->acquire('site:one', 'alice', str_repeat('a', 32));
    }

    public static function concurrentEditors(): array
    {
        return [['files', false], ['dbal', false], ['files', true], ['dbal', true]];
    }

    #[DataProvider('concurrentEditors')]
    public function testConcurrentEditorsHaveExactlyOneUserAfterAllRequestsExit(string $backend, bool $sameUser): void
    {
        $processes = [];
        try {
            for ($id = 0; $id < 4; $id++) {
                $process = proc_open([PHP_BINARY, __DIR__ . '/Fixtures/editing-lock-worker.php',
                    $this->directory, $backend, (string)$id, $sameUser ? 'shared-user' : ''], [
                    1 => ['file', $this->directory . '/output-' . $id, 'w'],
                    2 => ['file', $this->directory . '/error-' . $id, 'w']
                ], $pipes);
                self::assertIsResource($process);
                $processes[] = $process;
            }
            foreach (['ready', 'done'] as $phase) {
                $deadline = microtime(true) + 20;
                while (count(glob($this->directory . '/' . $phase . '-*') ?: []) < 4) {
                    if (microtime(true) >= $deadline) {
                        $errors = array_map('file_get_contents', glob($this->directory . '/error-*') ?: []);
                        self::fail('Editing lock workers timed out: ' . implode("\n", $errors));
                    }
                    usleep(10000);
                }
                file_put_contents($this->directory . '/go', 'go');
            }
            foreach ($processes as $process) {
                self::assertSame(0, proc_close($process));
            }
            $processes = [];
            $results = array_map('file_get_contents', glob($this->directory . '/done-*'));
            self::assertSame($sameUser ? 4 : 1, array_sum(array_map('intval', $results)));
            $Locks = new EditingLocks($this->store($backend));
            self::assertNotNull($Locks->status('site:concurrent'));

            if ($sameUser) {
                for ($id = 0; $id < 4; $id++) {
                    self::assertTrue($Locks->refresh('site:concurrent', 'shared-user', str_repeat(dechex($id + 1), 32)));
                }
            }
        } finally {
            foreach ($processes as $process) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
        }
    }
}
