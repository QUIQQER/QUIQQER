<?php

namespace QUI\Projects;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Lock\EditingLocks;
use QUI\Lock\Locker;
use QUI\Projects\Site\Edit;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Lock\Store\FlockStore;

class SiteEditingLockTest extends TestCase
{
    private mixed $previousUsers;
    private string $directory;
    private EditingLocks $Locks;
    private QUI\Users\User $User;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/quiqqer-site-lease-' . bin2hex(random_bytes(8));
        Locker::setProcessLockStore(new FlockStore($this->directory));
        $this->Locks = new EditingLocks(new ArrayAdapter());
        (new \ReflectionProperty(Locker::class, 'EditingLocks'))->setValue(null, $this->Locks);
        $Property = new \ReflectionProperty(QUI::class, 'Users');
        $this->previousUsers = $Property->getValue();
        $this->User = $this->createMock(QUI\Users\User::class);
        $this->User->method('getUUID')->willReturn('alice');
        $Users = $this->createMock(QUI\Users\Manager::class);
        $Users->method('getUserBySession')->willReturn($this->User);
        $Users->method('isSystemUser')->willReturn(false);
        $Property->setValue(null, $Users);
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(QUI::class, 'Users'))->setValue(null, $this->previousUsers);
        Locker::setProcessLockStore(null);
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    private function site(): Edit
    {
        $Site = $this->getMockBuilder(Edit::class)->disableOriginalConstructor()
            ->onlyMethods(['getLockKey', 'checkPermission'])->getMock();
        $Site->method('getLockKey')->willReturn('test_de_123');
        return $Site;
    }

    public function testSiteUsesUserUuidAndASeparateTokenForEachEditor(): void
    {
        $Site = $this->site();
        $one = str_repeat('a', 32);
        $two = str_repeat('b', 32);
        self::assertTrue($Site->acquireEditingLock($one));
        self::assertFalse($Site->isLockedFromOther());
        self::assertFalse($Site->acquireEditingLock($two));
        $Site->releaseEditingLock($two);
        self::assertTrue($Site->refreshLock($one));
        $Site->releaseEditingLock($one);
        self::assertTrue($Site->acquireEditingLock($two));
        self::assertFalse($Site->refreshLock($one));
    }

    public function testAnotherUserCannotRefreshOrReleaseTheLease(): void
    {
        $token = str_repeat('a', 32);
        $this->Locks->acquire('site:test_de_123', 'bob', $token);
        $Site = $this->site();
        self::assertSame('bob', $Site->isLockedFromOther());
        self::assertFalse($Site->refreshLock($token));
        $Site->releaseEditingLock($token);
        self::assertSame('bob', $Site->isLocked());
    }

    public function testOrdinaryEditorCannotForceUnlockEvenWithEditPermission(): void
    {
        $this->User->method('isSU')->willReturn(false);
        $this->Locks->acquire('site:test_de_123', 'bob', str_repeat('a', 32));
        $this->expectException(QUI\Permissions\Exception::class);
        $this->site()->unlockWithRights();
    }

    public function testSuperuserCanForceUnlockButOldRequestsCannotReleaseTheSuccessor(): void
    {
        $this->User->method('isSU')->willReturn(true);
        $Site = $this->site();
        $old = str_repeat('a', 32);
        $new = str_repeat('b', 32);
        self::assertTrue($Site->acquireEditingLock($old));
        $Site->unlockWithRights();
        self::assertTrue($Site->acquireEditingLock($new));
        $Site->releaseEditingLock($old);
        self::assertTrue($Site->refreshLock($new));
    }

    public function testStaleSaveIsRejectedBeforeTouchingTheSite(): void
    {
        $Site = $this->site();
        $Site->expects(self::never())->method('checkPermission');
        $this->Locks->acquire('site:test_de_123', 'alice', str_repeat('b', 32));
        $this->expectExceptionCode(703);
        $Site->saveWithLock(str_repeat('a', 32));
    }

    public function testAcquisitionRequiresTheSiteEditPermission(): void
    {
        $Site = $this->site();
        $Site->method('checkPermission')->willThrowException(new QUI\Permissions\Exception('Denied', 403));
        $this->expectExceptionCode(403);
        $Site->acquireEditingLock(str_repeat('a', 32));
    }
}
