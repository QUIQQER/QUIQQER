<?php

namespace QUI\Users;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User as UserInterface;
use ReflectionProperty;
use Throwable;

class UserDbalLifecycleTest extends TestCase
{
    private const TEST_PREFIX = 'codex-dbal-test-user-';

    private ?UserInterface $previousSessionUser = null;

    public static function setUpBeforeClass(): void
    {
        self::skipIfDatabaseIsUnavailable();
        self::cleanupTestUsers();
    }

    protected function setUp(): void
    {
        self::skipIfDatabaseIsUnavailable();

        $this->previousSessionUser = self::replaceSessionUser(QUI::getUsers()->getSystemUser());
    }

    protected function tearDown(): void
    {
        self::cleanupTestUsers();

        if ($this->previousSessionUser !== null) {
            self::replaceSessionUser($this->previousSessionUser);
        }
    }

    public static function tearDownAfterClass(): void
    {
        self::cleanupTestUsers();
    }

    public function testUserCanBeCreatedChangedActivatedDeactivatedAndDeleted(): void
    {
        $Users = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();
        $username = self::TEST_PREFIX . uniqid();

        $User = $Users->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid',
            'firstname' => 'DBAL',
            'lastname' => 'Lifecycle'
        ], $SystemUser);

        $this->assertSame($username, $User->getUsername());
        $this->assertFalse($User->isActive());
        $this->assertTrue($Users->usernameExists($username));

        $changedFirstname = 'DBAL Changed';
        $User->setAttribute('firstname', $changedFirstname);
        $User->save($SystemUser);

        $ReloadedUser = $Users->get($User->getUUID());
        $this->assertSame($changedFirstname, $ReloadedUser->getAttribute('firstname'));

        $ReloadedUser->setPassword('codex-dbal-test-password', $SystemUser);
        $this->assertSame(1, $ReloadedUser->activate('', $SystemUser));
        $this->assertTrue($Users->get($ReloadedUser->getUUID())->isActive());

        $this->assertTrue($ReloadedUser->deactivate($SystemUser));
        $this->assertFalse($Users->get($ReloadedUser->getUUID())->isActive());

        $this->assertTrue($Users->deleteUser($ReloadedUser->getUUID()));
        $this->assertFalse($Users->usernameExists($username));
    }

    private static function skipIfDatabaseIsUnavailable(): void
    {
        try {
            $Connection = self::getConnection();
            $Connection->executeQuery(
                'SELECT 1 FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Manager::table()) . ' LIMIT 1'
            )->free();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER database is not available: ' . $Exception->getMessage());
        }
    }

    private static function getConnection(): Connection
    {
        return QUI::getDataBaseConnection();
    }

    private static function cleanupTestUsers(): void
    {
        try {
            $Connection = self::getConnection();
            $Platform = $Connection->getDatabasePlatform();
            $usersTable = QUI\Utils\Doctrine::quoteIdentifier(Manager::table());

            $rows = $Connection->createQueryBuilder()
                ->select('id', 'uuid')
                ->from($usersTable)
                ->where('username LIKE :username')
                ->setParameter('username', self::TEST_PREFIX . '%')
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                $Connection->delete(
                    QUI\Utils\Doctrine::quoteIdentifier(Manager::tableAddress()),
                    ['userUuid' => $row['uuid']]
                );

                $Connection->delete(
                    QUI\Utils\Doctrine::quoteIdentifier(QUI\Workspace\Manager::table()),
                    ['uid' => $row['uuid']]
                );

                $Connection->delete(
                    QUI\Utils\Doctrine::quoteIdentifier(QUI\Workspace\Manager::table()),
                    ['uid' => $row['id']]
                );
            }

            $Connection->createQueryBuilder()
                ->delete($usersTable)
                ->where($Platform->quoteSingleIdentifier('username') . ' LIKE :username')
                ->setParameter('username', self::TEST_PREFIX . '%')
                ->executeStatement();
        } catch (Throwable) {
            // The availability check reports DB problems. Cleanup should not hide the test result.
        }
    }

    private static function replaceSessionUser(UserInterface $User): ?UserInterface
    {
        $Users = QUI::getUsers();
        $Property = new ReflectionProperty($Users, 'Session');
        $Property->setAccessible(true);

        $PreviousUser = $Property->getValue($Users);
        $Property->setValue($Users, $User);

        return $PreviousUser instanceof UserInterface ? $PreviousUser : null;
    }
}
