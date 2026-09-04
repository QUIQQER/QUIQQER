<?php

namespace QUI\Security;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use ReflectionProperty;

class ThrottleTest extends TestCase
{
    private Connection $Connection;
    private ?Connection $previousConnection;
    private ReflectionProperty $connectionProperty;
    private string $table;

    protected function setUp(): void
    {
        $this->connectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $this->previousConnection = $this->connectionProperty->getValue();
        $this->Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        $this->connectionProperty->setValue(null, $this->Connection);

        $this->table = Throttle::table();
        $quotedTable = $this->Connection->getDatabasePlatform()->quoteIdentifier($this->table);

        $this->Connection->executeStatement(
            'CREATE TABLE ' . $quotedTable . ' ('
            . 'throttleKey VARCHAR(64) PRIMARY KEY, '
            . 'package VARCHAR(255) NOT NULL, '
            . 'subjectKey VARCHAR(64) NOT NULL, '
            . 'reservationId VARCHAR(32) NOT NULL, '
            . 'expiresAt BIGINT NOT NULL'
            . ')'
        );
    }

    protected function tearDown(): void
    {
        $this->Connection->close();
        $this->connectionProperty->setValue(null, $this->previousConnection);
    }

    public function testAcquireBlocksSameUserPackageAndAction(): void
    {
        $User = $this->createUser('user-a');

        $FirstDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);
        $SecondDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        self::assertTrue($FirstDecision->isAllowed());
        self::assertFalse($SecondDecision->isAllowed());
        self::assertSame($FirstDecision->getRetryAt(), $SecondDecision->getRetryAt());
        self::assertSame(1, $this->countRows());
    }

    public function testDifferentUsersPackagesAndActionsUseSeparateBuckets(): void
    {
        $FirstUser = $this->createUser('user-a');
        $SecondUser = $this->createUser('user-b');

        self::assertTrue(Throttle::acquireForUser($FirstUser, 'quiqqer/core', 'action-a', 60)->isAllowed());
        self::assertTrue(Throttle::acquireForUser($SecondUser, 'quiqqer/core', 'action-a', 60)->isAllowed());
        self::assertTrue(Throttle::acquireForUser($FirstUser, 'quiqqer/core', 'action-b', 60)->isAllowed());
        self::assertTrue(Throttle::acquireForUser($FirstUser, 'vendor/package', 'action-a', 60)->isAllowed());

        self::assertSame(4, $this->countRows());
    }

    public function testExpiredReservationCanBeAcquiredAgain(): void
    {
        $User = $this->createUser('user-a');
        $FirstDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        $this->Connection->update($this->table, ['expiresAt' => 0], []);

        $SecondDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        self::assertTrue($FirstDecision->isAllowed());
        self::assertTrue($SecondDecision->isAllowed());
        self::assertSame(1, $this->countRows());
    }

    public function testOldDecisionCannotReleaseNewerReservation(): void
    {
        $User = $this->createUser('user-a');
        $OldDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        $this->Connection->update($this->table, ['expiresAt' => 0], []);
        $NewDecision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        $OldDecision->release();

        self::assertTrue($NewDecision->isAllowed());
        self::assertFalse(
            Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60)->isAllowed()
        );
        self::assertSame(1, $this->countRows());
    }

    public function testReleaseIsIdempotentAndAllowsAnotherAcquire(): void
    {
        $User = $this->createUser('user-a');
        $Decision = Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60);

        $Decision->release();
        $Decision->release();

        self::assertSame(0, $this->countRows());
        self::assertTrue(
            Throttle::acquireForUser($User, 'quiqqer/core', 'mail-action', 60)->isAllowed()
        );
    }

    public function testCleanupExpiredLeavesActiveReservations(): void
    {
        Throttle::acquireForUser($this->createUser('expired-user'), 'quiqqer/core', 'mail-action', 60);
        Throttle::acquireForUser($this->createUser('active-user'), 'quiqqer/core', 'mail-action', 60);

        $this->Connection->createQueryBuilder()
            ->update($this->table)
            ->set('expiresAt', ':expiresAt')
            ->where('subjectKey = :subjectKey')
            ->setParameter('expiresAt', 0)
            ->setParameter('subjectKey', hash('sha256', "user\0expired-user"))
            ->executeStatement();

        self::assertSame(1, Throttle::cleanupExpired());
        self::assertSame(1, $this->countRows());
    }

    public function testClearForUserLeavesOtherUsersReservations(): void
    {
        $FirstUser = $this->createUser('user-a');
        $SecondUser = $this->createUser('user-b');

        Throttle::acquireForUser($FirstUser, 'quiqqer/core', 'action-a', 60);
        Throttle::acquireForUser($FirstUser, 'quiqqer/core', 'action-b', 60);
        Throttle::acquireForUser($SecondUser, 'quiqqer/core', 'action-a', 60);

        self::assertSame(2, Throttle::clearForUser($FirstUser));
        self::assertSame(1, $this->countRows());
        self::assertFalse(Throttle::acquireForUser($SecondUser, 'quiqqer/core', 'action-a', 60)->isAllowed());
    }

    public function testClearForPackageLeavesOtherPackagesReservations(): void
    {
        $User = $this->createUser('user-a');

        Throttle::acquireForUser($User, 'quiqqer/core', 'action-a', 60);
        Throttle::acquireForUser($User, 'quiqqer/core', 'action-b', 60);
        Throttle::acquireForUser($User, 'vendor/package', 'action-a', 60);

        self::assertSame(2, Throttle::clearForPackage('quiqqer/core'));
        self::assertSame(1, $this->countRows());
        self::assertFalse(Throttle::acquireForUser($User, 'vendor/package', 'action-a', 60)->isAllowed());
    }

    private function createUser(string $uuid): User
    {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uuid);

        return $User;
    }

    private function countRows(): int
    {
        return (int)$this->Connection->fetchOne('SELECT COUNT(*) FROM ' . $this->table);
    }
}
