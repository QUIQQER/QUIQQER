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
final class UserPasswordAuthorizationTest extends TestCase
{
    private const AJAX_FUNCTION = 'ajax_users_set_password';
    private const TEST_PREFIX = 'password-auth-fix-';

    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousPermissionUser;
    private mixed $previousSession;

    /** @var array<string, User> */
    private array $users = [];

    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $permissionUserProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();
        $this->previousSession = QUI::$Session;

        QUI::$Ajax = new Ajax();
        require_once OPT_DIR . 'quiqqer/core/admin/ajax/users/set/password.php';

        $Root = $this->getRootUser();
        $this->setActor($Root);

        $this->users['manager'] = $this->createUser('manager');
        $this->users['normal'] = $this->createUser('normal');
        $this->users['superuser'] = $this->createUser('superuser');

        QUI::getPermissionManager()->setPermissions($this->users['manager'], [
            'quiqqer.admin' => true,
            'quiqqer.admin.users.edit' => true
        ], $Root);

        $this->users['superuser']->setAttribute('su', true);
        $this->users['superuser']->save(QUI::getUsers()->getSystemUser());
        $this->users['superuser']->refresh();

        self::assertTrue($this->users['superuser']->isSU());
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
            QUI::$Ajax = $this->previousAjax;
            QUI::$Session = $this->previousSession;
        }

        parent::tearDown();

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testNonSuperUserManagerCannotReplaceSuperUserPasswordThroughAjax(): void
    {
        $Manager = $this->users['manager'];
        $SuperUser = $this->users['superuser'];
        $originalPassword = self::TEST_PREFIX . 'superuser-original';
        $replacementPassword = self::TEST_PREFIX . 'superuser-replacement';

        $SuperUser->setPassword($originalPassword, QUI::getUsers()->getSystemUser());
        $this->setActor($Manager);

        $response = QUI::getAjax()->callRequestFunction(self::AJAX_FUNCTION, [
            'uid' => $SuperUser->getUUID(),
            'pw1' => $replacementPassword,
            'pw2' => $replacementPassword
        ]);

        self::assertArrayHasKey('Exception', $response);
        self::assertTrue($SuperUser->checkPassword($originalPassword));
        self::assertFalse($SuperUser->checkPassword($replacementPassword));
    }

    public function testPasswordPermissionMatrix(): void
    {
        $Manager = $this->users['manager'];
        $Normal = $this->users['normal'];
        $SuperUser = $this->users['superuser'];
        $Root = $this->getRootUser();
        $System = QUI::getUsers()->getSystemUser();

        $this->setActor($Manager);
        $Normal->setPassword(self::TEST_PREFIX . 'managed-normal');
        self::assertTrue($Normal->checkPassword(self::TEST_PREFIX . 'managed-normal'));

        try {
            $SuperUser->setPassword(self::TEST_PREFIX . 'managed-superuser');
            self::fail('A non-superuser user manager must not replace a superuser password.');
        } catch (QUI\Permissions\Exception) {
            self::assertFalse($SuperUser->checkPassword(self::TEST_PREFIX . 'managed-superuser'));
        }

        $this->setActor($Normal);
        $Normal->setPassword(self::TEST_PREFIX . 'self-service');
        self::assertTrue($Normal->checkPassword(self::TEST_PREFIX . 'self-service'));

        $this->setActor($Root);
        $SuperUser->setPassword(self::TEST_PREFIX . 'set-by-superuser');
        self::assertTrue($SuperUser->checkPassword(self::TEST_PREFIX . 'set-by-superuser'));

        $this->setActor($Manager);
        $SuperUser->setPassword(self::TEST_PREFIX . 'set-by-system', $System);
        self::assertTrue($SuperUser->checkPassword(self::TEST_PREFIX . 'set-by-system'));
    }

    private function createUser(string $role): User
    {
        $username = self::TEST_PREFIX . $role . '-' . bin2hex(random_bytes(5));
        $System = QUI::getUsers()->getSystemUser();
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $System);

        self::assertInstanceOf(User::class, $User);

        $User->setPassword(self::TEST_PREFIX . 'initial-' . $role, $System);
        $User->activate('', $System);

        return $User;
    }

    private function getRootUser(): User
    {
        $Root = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));

        self::assertInstanceOf(User::class, $Root);
        self::assertTrue($Root->isSU(), 'The local fixture root user must be an SU.');

        return $Root;
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

    private function cleanupFixtures(): void
    {
        $System = QUI::getUsers()->getSystemUser();
        $this->setActor($System);
        $Connection = QUI::getDataBaseConnection();
        $permissionTable = QUI::getPermissionManager()::table() . '2users';

        foreach ($this->users as $User) {
            $Connection->delete($permissionTable, ['user_id' => (string)$User->getUUID()]);

            try {
                QUI::getUsers()->get($User->getUUID())->delete($System);
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

        self::assertSame(0, $remainingUsers, 'Password authorization fixtures were not fully removed.');
    }

    private function skipIfDatabaseIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $this->getRootUser();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database fixtures are unavailable: ' . $Exception->getMessage());
        }
    }
}
