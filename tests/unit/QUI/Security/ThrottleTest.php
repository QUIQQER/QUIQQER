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
            . 'expiresAt BIGINT NOT NULL, attempts INTEGER NOT NULL DEFAULT 0'
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


    public function testIpBudgetExpiresWithoutSliding(): void
    {
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 2, 900));
        $first = $this->Connection->fetchAssociative('SELECT * FROM ' . $this->table);
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 2, 900));
        self::assertFalse(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 2, 900));
        $last = $this->Connection->fetchAssociative('SELECT * FROM ' . $this->table);
        self::assertSame($first['expiresAt'], $last['expiresAt']);
        self::assertSame(2, (int)$last['attempts']);
        self::assertStringNotContainsString('192.0.2.1', json_encode($last));
        $this->Connection->update($this->table, ['expiresAt' => time() - 1], []);
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 2, 900));
        self::assertSame(1, (int)$this->Connection->fetchOne('SELECT attempts FROM ' . $this->table));
    }

    public function testEquivalentIpAddressesCannotSplitTheBudget(): void
    {
        foreach (
            [
            ['192.0.2.1', '::ffff:192.0.2.1'],
            ['2001:db8::1', '2001:0db8:0000:0000:0000:0000:0000:0001']
            ] as [$first, $second]
        ) {
            self::assertTrue(Throttle::acquireForIp($first, 'quiqqer/core', 'login', 1, 900));
            self::assertFalse(Throttle::acquireForIp($second, 'quiqqer/core', 'login', 1, 900));
        }
    }

    public function testIpActionsAndUserMailReservationsRemainIndependent(): void
    {
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 1, 900));
        self::assertTrue(Throttle::acquireForIp('192.0.2.2', 'quiqqer/core', 'login', 1, 900));
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'lookup', 1, 900));
        self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'vendor/package', 'login', 1, 900));
        $User = $this->createUser('192.0.2.1');
        $Decision = Throttle::acquireForUser($User, 'quiqqer/core', 'login', 60);
        self::assertTrue($Decision->isAllowed());
        self::assertSame(1, Throttle::clearForUser($User));
        $Decision->release();
        self::assertFalse(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 1, 900));
        self::assertSame(3, Throttle::clearForPackage('quiqqer/core'));
        self::assertSame(1, $this->countRows());
    }

    public function testDeniedIpReservationLeavesOuterTransactionUsable(): void
    {
        $this->Connection->transactional(function (): void {
            self::assertTrue(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 1, 900));
            self::assertFalse(Throttle::acquireForIp('192.0.2.1', 'quiqqer/core', 'login', 1, 900));
            self::assertTrue(Throttle::acquireForIp('192.0.2.2', 'quiqqer/core', 'login', 1, 900));
            self::assertSame(2, $this->countRows());
        });
    }

    public function testInvalidIpDoesNotCreateReservations(): void
    {
        try {
            Throttle::acquireForIp('invalid', 'quiqqer/core', 'login', 1, 900);
            self::fail('Invalid source accepted.');
        } catch (\InvalidArgumentException) {
            self::assertSame(0, $this->countRows());
        }
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
