<?php

declare(strict_types=1);

namespace QUI\Users;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Permissions\Permission;
use QUI\System\Console\Session as ConsoleSession;
use ReflectionProperty;
use Throwable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class UserGroupAssignmentAuthorizationTest extends TestCase
{
    private const AJAX_FUNCTION = 'ajax_users_save';
    private const TEST_PREFIX = 'cwa-user-group-fix-';

    private mixed $previousSession;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousPermissionUser;

    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $permissionUserProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->previousSession = QUI::$Session;
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();

        QUI::$Ajax = new Ajax();
        require_once OPT_DIR . 'quiqqer/core/admin/ajax/users/save.php';

        $Root = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        self::assertTrue($Root->isSU(), 'The local fixture root user must be an SU.');

        $this->setActor($Root);
        $this->cleanupFixtures();
    }

    protected function tearDown(): void
    {
        $cleanupFailure = null;

        try {
            $this->cleanupFixtures();
        } catch (Throwable $Exception) {
            $cleanupFailure = $Exception;
        } finally {
            $this->managerSessionProperty->setValue(QUI::getUsers(), $this->previousManagerSession);
            $this->permissionUserProperty->setValue(null, $this->previousPermissionUser);
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
        }

        parent::tearDown();

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testBackendUserCannotAssignOwnGroupsWithoutUserEditPermission(): void
    {
        $User = $this->createBackendUser(false);
        $rootGroupUuid = (string)QUI::getGroups()->get(QUI::conf('globals', 'root'))->getUUID();
        $groupsBefore = $User->getGroups(false);
        $firstNameBefore = (string)$User->getAttribute('firstname');

        $this->setActor($User);

        $response = $this->invokeSave($User, [
            'firstname' => 'Unauthorized mutation',
            'usergroup' => [$rootGroupUuid]
        ]);

        self::assertArrayHasKey('Exception', $response);

        $User->refresh();
        self::assertSame($groupsBefore, $User->getGroups(false));
        self::assertSame($firstNameBefore, $User->getAttribute('firstname'));
        self::assertNotContains($rootGroupUuid, $User->getGroups(false));
    }

    public function testSelfProfileSaveWithoutGroupAssignmentRemainsAllowed(): void
    {
        $User = $this->createBackendUser(false);
        $firstName = 'Allowed profile mutation';

        $this->setActor($User);
        $response = $this->invokeSave($User, ['firstname' => $firstName]);

        self::assertArrayNotHasKey('Exception', $response);

        $User->refresh();
        self::assertSame($firstName, $User->getAttribute('firstname'));
    }

    public function testBackendUserWithUserEditPermissionCanAssignGroups(): void
    {
        $User = $this->createBackendUser(true);
        $rootGroupUuid = (string)QUI::getGroups()->get(QUI::conf('globals', 'root'))->getUUID();

        $this->setActor($User);
        $response = $this->invokeSave($User, ['usergroup' => [$rootGroupUuid]]);

        self::assertArrayNotHasKey('Exception', $response);

        $User->refresh();
        self::assertContains($rootGroupUuid, $User->getGroups(false));
    }

    private function createBackendUser(bool $canEditUsers): User
    {
        $username = self::TEST_PREFIX . bin2hex(random_bytes(5));
        $System = QUI::getUsers()->getSystemUser();
        $Root = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $System);

        self::assertInstanceOf(User::class, $User);
        QUI::getPermissionManager()->setPermissions($User, [
            'quiqqer.admin' => true,
            'quiqqer.admin.users.edit' => $canEditUsers
        ], $Root);

        $User->setPassword('Codex-user-group-fix-' . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function setActor(UserInterface $User): void
    {
        $Session = new ConsoleSession();
        $Session->set('uid', (string)$User->getUUID());
        $Session->set('username', $User->getUsername());
        $Session->set('auth', 1);
        $Session->set('auth-primary', 1);
        $Session->set('auth-secondary', 1);

        QUI::$Session = $Session;
        $this->managerSessionProperty->setValue(QUI::getUsers(), $User);
        $this->permissionUserProperty->setValue(null, null);
    }

    /** @param array<string, mixed> $attributes */
    private function invokeSave(User $User, array $attributes): array
    {
        return QUI::getAjax()->callRequestFunction(self::AJAX_FUNCTION, [
            'uid' => $User->getUUID(),
            'attributes' => json_encode($attributes, JSON_THROW_ON_ERROR)
        ]);
    }

    private function cleanupFixtures(): void
    {
        $System = QUI::getUsers()->getSystemUser();
        $this->setActor($System);
        $Connection = QUI::getDataBaseConnection();
        $permissionTable = QUI::getPermissionManager()::table() . '2users';
        $userRows = $Connection->createQueryBuilder()
            ->select('uuid')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where('username LIKE :prefix')
            ->setParameter('prefix', self::TEST_PREFIX . '%')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($userRows as $userRow) {
            $uuid = (string)$userRow['uuid'];
            $Connection->delete($permissionTable, ['user_id' => $uuid]);

            try {
                QUI::getUsers()->get($uuid)->delete($System);
            } catch (QUI\Users\Exception $Exception) {
                if ($Exception->getCode() !== 404) {
                    throw $Exception;
                }
            }
        }

        $remainingUsers = (int)$Connection->createQueryBuilder()
            ->select('COUNT(id)')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(Manager::table()))
            ->where('username LIKE :prefix')
            ->setParameter('prefix', self::TEST_PREFIX . '%')
            ->executeQuery()
            ->fetchOne();

        self::assertSame(0, $remainingUsers, 'User fixtures were not fully removed.');
    }

    private function skipIfDatabaseIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery(
                'SELECT 1 FROM ' . QUI\Utils\Doctrine::quoteIdentifier(Manager::table()) . ' LIMIT 1'
            )->free();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database fixtures are unavailable: ' . $Exception->getMessage());
        }
    }
}
