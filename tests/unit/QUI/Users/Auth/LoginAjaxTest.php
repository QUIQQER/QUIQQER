<?php

namespace QUI\Users\Auth;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Config;
use QUI\Events\Manager as EventsManager;
use QUI\Interfaces\Users\User;
use QUI\Session;
use QUI\Users\AuthenticatorInterface;
use QUI\Users\Manager as UserManager;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LoginAjaxTest extends TestCase
{
    protected function setUp(): void
    {
        $Connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        (new ReflectionProperty(QUI::class, 'QueryBuilder'))->setValue(null, $Connection);
        $table = $Connection->getDatabasePlatform()->quoteIdentifier(QUI\Security\Throttle::table());
        $Connection->executeStatement(
            'CREATE TABLE ' . $table . ' (throttleKey VARCHAR(64) PRIMARY KEY, '
            . 'package VARCHAR(255) NOT NULL, subjectKey VARCHAR(64) NOT NULL, '
            . 'reservationId VARCHAR(32) NOT NULL, expiresAt BIGINT NOT NULL, '
            . 'attempts INTEGER NOT NULL DEFAULT 0)'
        );
        QUI::getRequest()->server->set('REMOTE_ADDR', '192.0.2.1');
    }


    public function testLoginBudgetSurvivesNewSessionsAndUnknownUsernames(): void
    {
        $Config = $this->createMock(Config::class);
        $Config->method('get')->willReturnCallback(
            static fn(string $section, ?string $key = null): mixed =>
                $section === 'auth_settings' && $key === 'loginIpLimit' ? 2 : false
        );
        QUI::$Conf = $Config;
        QUI::$Events = $this->createMock(EventsManager::class);
        QUI::$Ajax = new Ajax();
        $Nobody = $this->createMock(User::class);
        $Nobody->method('getUUID')->willReturn('');
        $Users = $this->createMock(UserManager::class);
        $Users->method('getUserBySession')->willReturn($Nobody);
        $Users->expects(self::exactly(2))->method('authenticate')->willReturnCallback(
            static function (): never {
                $Exception = new QUI\Users\UserAuthException(['quiqqer/core', 'exception.login.fail'], 401);
                $Exception->setAttribute('reason', UserManager::AUTH_ERROR_AUTH_ERROR);
                throw $Exception;
            }
        );
        QUI::$Users = $Users;
        $Handler = $this->createMock(Handler::class);
        $Handler->method('getGlobalFrontendAuthenticators')->willReturn([QUIQQER::class]);
        $Handler->method('getGlobalBackendAuthenticators')->willReturn([QUIQQER::class]);
        (new ReflectionProperty(Handler::class, 'Instance'))->setValue(null, $Handler);
        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';
        $login = Ajax::getRegisteredCallables()['ajax_users_login']['callable'];

        foreach (['known-user', 'unknown-user', 'another-unknown-user'] as $index => $username) {
            QUI::$Session = new QUI\System\Console\Session();
            QUI::getRequest()->server->set('HTTP_X_FORWARDED_FOR', '198.51.100.' . ($index + 1));
            try {
                $login(QUIQQER::class, ['username' => $username, 'password' => 'wrong'], 'primary');
                self::fail('Authentication unexpectedly succeeded.');
            } catch (QUI\Users\UserAuthException $Exception) {
                self::assertSame($index === 2 ? 429 : 401, $Exception->getCode());
            }
        }

        self::assertSame(2, (int)QUI::getDataBaseConnection()->fetchOne(
            'SELECT attempts FROM ' . QUI\Security\Throttle::table()
        ));
    }


    public function testMissingSourceCannotStartAuthentication(): void
    {
        QUI::getRequest()->server->remove('REMOTE_ADDR');
        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::never())->method('fireEvent');
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();
        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';
        $login = Ajax::getRegisteredCallables()['ajax_users_login']['callable'];

        $this->expectException(QUI\Users\UserAuthException::class);
        $this->expectExceptionCode(429);
        $login(QUIQQER::class, ['username' => 'unknown', 'password' => 'wrong'], 'primary');
    }

    public function testUnavailableThrottleStorageCannotStartAuthentication(): void
    {
        QUI::getDataBaseConnection()->createSchemaManager()->dropTable(QUI\Security\Throttle::table());
        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::never())->method('fireEvent');
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();
        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';
        $login = Ajax::getRegisteredCallables()['ajax_users_login']['callable'];

        $this->expectException(\Doctrine\DBAL\Exception::class);
        $login(QUIQQER::class, ['username' => 'unknown', 'password' => 'wrong'], 'primary');
    }

    public static function invalidIpLimits(): array
    {
        return [[null], [0], [-1], ['invalid'], [1000001]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('invalidIpLimits')]
    public function testInvalidLimitUsesDefaultInsteadOfDisablingProtection(mixed $limit): void
    {
        for ($i = 0; $i < 59; $i++) {
            self::assertTrue(QUI\Security\Throttle::acquireForIp(
                '192.0.2.1',
                'quiqqer/core',
                'users.login',
                60,
                900
            ));
        }

        $Config = $this->createMock(Config::class);
        $Config->method('get')->willReturnCallback(
            static fn(string $section, ?string $key = null): mixed =>
                $section === 'auth_settings' && $key === 'loginIpLimit' ? $limit : false
        );
        QUI::$Conf = $Config;
        QUI::$Session = new QUI\System\Console\Session();
        QUI::$Events = $this->createMock(EventsManager::class);
        QUI::$Ajax = new Ajax();
        $Handler = $this->createMock(Handler::class);
        $Handler->method('getGlobalFrontendAuthenticators')->willReturn([]);
        $Handler->method('getGlobalBackendAuthenticators')->willReturn([]);
        (new ReflectionProperty(Handler::class, 'Instance'))->setValue(null, $Handler);
        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';
        $login = Ajax::getRegisteredCallables()['ajax_users_login']['callable'];

        try {
            $login('unconfigured-authenticator', [], 'primary');
            self::fail('Unconfigured authenticator accepted.');
        } catch (QUI\Users\Auth\Exception $Exception) {
            self::assertSame(404, $Exception->getCode());
        }

        $this->expectException(QUI\Users\UserAuthException::class);
        $this->expectExceptionCode(429);
        $login('unconfigured-authenticator', [], 'primary');
    }

    public function testCrossSiteLoginRequestIsRejectedBeforeAuthenticationStarts(): void
    {
        $Session = $this->createMock(Session::class);
        $Session->expects(self::never())->method('set');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::never())->method('fireEvent');

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(403);

        $login(
            'TestPrimaryAuthenticator',
            ['username' => 'attacker', 'password' => 'correct-password'],
            SessionFailureCounter::STEP_PRIMARY,
            ['TestPrimaryAuthenticator']
        );
    }

    public function testForeignLoginOriginIsRejectedWithoutFetchMetadata(): void
    {
        $Session = $this->createMock(Session::class);
        $Session->expects(self::never())->method('set');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::never())->method('fireEvent');

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();
        unset($_SERVER['HTTP_SEC_FETCH_SITE']);
        $_SERVER['HTTP_ORIGIN'] = 'https://attacker.example';
        $_SERVER['HTTP_HOST'] = 'victim.example';

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(403);

        $login(
            'TestPrimaryAuthenticator',
            ['username' => 'attacker', 'password' => 'correct-password'],
            SessionFailureCounter::STEP_PRIMARY,
            ['TestPrimaryAuthenticator']
        );
    }

    public function testPrimaryAuthenticatorCannotBeReusedAsSecondaryAuthenticator(): void
    {
        $authenticator = 'TestPrimaryAuthenticator';
        $Session = $this->createMock(Session::class);

        $Session->expects(self::once())
            ->method('set')
            ->with('inAuthentication', 1);
        $Session->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function (string $key) use ($authenticator): int {
                return match ($key) {
                    'auth-primary' => 1,
                    'auth-' . $authenticator => 1
                };
            });
        $Session->expects(self::once())
            ->method('remove')
            ->with('inAuthentication');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userLoginAjaxStart');

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            ['password' => 'correct-password'],
            SessionFailureCounter::STEP_SECONDARY,
            [$authenticator]
        );

        self::assertFalse($result);
    }

    public function testSecondaryAuthenticationRequiresSuccessfulPrimaryAuthentication(): void
    {
        $authenticator = 'TestSecondaryAuthenticator';
        $Session = $this->createMock(Session::class);

        $Session->expects(self::once())
            ->method('set')
            ->with('inAuthentication', 1);
        $Session->expects(self::once())
            ->method('get')
            ->with('auth-primary')
            ->willReturn(0);
        $Session->expects(self::once())
            ->method('remove')
            ->with('inAuthentication');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userLoginAjaxStart');

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_SECONDARY,
            [$authenticator]
        );

        self::assertFalse($result);
    }

    public function testDifferentPrimaryAuthenticatorCannotBeUsedAsSecondaryAuthenticator(): void
    {
        $authenticator = 'DifferentPrimaryAuthenticator';
        $Session = $this->createMock(Session::class);

        $Session->expects(self::once())
            ->method('set')
            ->with('inAuthentication', 1);
        $Session->expects(self::exactly(2))
            ->method('get')
            ->willReturnCallback(static function (string $key) use ($authenticator): int {
                return match ($key) {
                    'auth-primary' => 1,
                    'auth-' . $authenticator => 0
                };
            });
        $Session->expects(self::once())
            ->method('remove')
            ->with('inAuthentication');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userLoginAjaxStart');

        $AuthHandler = $this->createMock(Handler::class);
        $AuthHandler->expects(self::once())
            ->method('getGlobalFrontendSecondaryAuthenticators')
            ->willReturn(['ConfiguredSecondaryAuthenticator']);

        $HandlerInstance = new ReflectionProperty(Handler::class, 'Instance');
        $HandlerInstance->setValue(null, $AuthHandler);

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_SECONDARY,
            [$authenticator]
        );

        self::assertFalse($result);
    }

    public function testSecondaryAuthenticatorMustBeEnabledForUser(): void
    {
        $authenticator = 'ConfiguredSecondaryAuthenticator';
        $uid = 'test-user-uuid';
        $Session = $this->createMock(Session::class);

        $Session->expects(self::once())
            ->method('set')
            ->with('inAuthentication', 1);
        $Session->expects(self::exactly(3))
            ->method('get')
            ->willReturnCallback(static function (string $key) use ($authenticator, $uid): int | string {
                return match ($key) {
                    'auth-primary' => 1,
                    'auth-' . $authenticator => 0,
                    'uid' => $uid
                };
            });
        $Session->expects(self::once())
            ->method('remove')
            ->with('inAuthentication');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userLoginAjaxStart');

        $AuthHandler = $this->createMock(Handler::class);
        $AuthHandler->expects(self::once())
            ->method('getGlobalFrontendSecondaryAuthenticators')
            ->willReturn([$authenticator]);

        $HandlerInstance = new ReflectionProperty(Handler::class, 'Instance');
        $HandlerInstance->setValue(null, $AuthHandler);

        $User = $this->createMock(User::class);
        $User->expects(self::once())
            ->method('hasAuthenticator')
            ->with($authenticator)
            ->willReturn(false);

        $Users = $this->createMock(UserManager::class);
        $Users->expects(self::once())
            ->method('get')
            ->with($uid)
            ->willReturn($User);

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Users = $Users;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_SECONDARY,
            [$authenticator]
        );

        self::assertFalse($result);
    }

    public function testConfiguredAuthenticatorMustSupportSecondaryAuthentication(): void
    {
        $authenticator = 'ConfiguredPrimaryOnlyAuthenticator';
        $uid = 'test-user-uuid';
        $Session = $this->createMock(Session::class);

        $Session->expects(self::once())
            ->method('set')
            ->with('inAuthentication', 1);
        $Session->expects(self::exactly(3))
            ->method('get')
            ->willReturnCallback(static function (string $key) use ($authenticator, $uid): int | string {
                return match ($key) {
                    'auth-primary' => 1,
                    'auth-' . $authenticator => 0,
                    'uid' => $uid
                };
            });
        $Session->expects(self::once())
            ->method('remove')
            ->with('inAuthentication');

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userLoginAjaxStart');

        $User = $this->createMock(User::class);
        $User->expects(self::once())
            ->method('hasAuthenticator')
            ->with($authenticator)
            ->willReturn(true);

        $Authenticator = $this->createMock(AuthenticatorInterface::class);
        $Authenticator->expects(self::once())
            ->method('isSecondaryAuthentication')
            ->willReturn(false);

        $AuthHandler = $this->createMock(Handler::class);
        $AuthHandler->expects(self::once())
            ->method('getGlobalFrontendSecondaryAuthenticators')
            ->willReturn([$authenticator]);
        $AuthHandler->expects(self::once())
            ->method('getAuthenticator')
            ->with($authenticator, $User)
            ->willReturn($Authenticator);

        $HandlerInstance = new ReflectionProperty(Handler::class, 'Instance');
        $HandlerInstance->setValue(null, $AuthHandler);

        $Users = $this->createMock(UserManager::class);
        $Users->expects(self::once())
            ->method('get')
            ->with($uid)
            ->willReturn($User);

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Users = $Users;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_SECONDARY,
            [$authenticator]
        );

        self::assertFalse($result);
    }

    public function testConfiguredAndEnabledSecondaryAuthenticatorIsExecuted(): void
    {
        $authenticator = 'ConfiguredSecondaryAuthenticator';
        $uid = 'test-user-uuid';
        $sessionValues = [
            'auth-primary' => 1,
            'auth-secondary' => 0,
            'auth' => 0,
            'auth-' . $authenticator => 0,
            'uid' => $uid
        ];
        $Session = $this->createMock(Session::class);
        $Session->method('get')
            ->willReturnCallback(static function (string $key) use (&$sessionValues): mixed {
                return $sessionValues[$key] ?? false;
            });
        $Session->method('set')
            ->willReturnCallback(static function (string $key, mixed $value) use (&$sessionValues): void {
                $sessionValues[$key] = $value;
            });
        $Session->method('remove')
            ->willReturnCallback(static function (string $key) use (&$sessionValues): void {
                unset($sessionValues[$key]);
            });

        $loginStartEvents = 0;
        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')
            ->willReturnCallback(static function (string $event) use (&$loginStartEvents): array {
                if ($event === 'userLoginAjaxStart') {
                    $loginStartEvents++;
                }

                return [];
            });

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uid);
        $User->method('getName')->willReturn('Test User');
        $User->method('getLang')->willReturn('en');
        $User->expects(self::once())
            ->method('hasAuthenticator')
            ->with($authenticator)
            ->willReturn(true);

        $Nobody = $this->createMock(User::class);
        $Nobody->method('getUUID')->willReturn('');

        $Authenticator = $this->createMock(AuthenticatorInterface::class);
        $Authenticator->expects(self::once())
            ->method('isSecondaryAuthentication')
            ->willReturn(true);

        $AuthHandler = $this->createMock(Handler::class);
        $AuthHandler->expects(self::once())
            ->method('getGlobalFrontendSecondaryAuthenticators')
            ->willReturn([$authenticator]);
        $AuthHandler->expects(self::once())
            ->method('getAuthenticator')
            ->with($authenticator, $User)
            ->willReturn($Authenticator);

        $HandlerInstance = new ReflectionProperty(Handler::class, 'Instance');
        $HandlerInstance->setValue(null, $AuthHandler);

        $sessionUserCall = 0;
        $Users = $this->createMock(UserManager::class);
        $Users->method('getUserBySession')
            ->willReturnCallback(static function () use (&$sessionUserCall, $Nobody, $User): User {
                return $sessionUserCall++ === 0 ? $Nobody : $User;
            });
        $Users->expects(self::once())
            ->method('get')
            ->with($uid)
            ->willReturn($User);
        $Users->expects(self::once())
            ->method('authenticate')
            ->willReturnCallback(static function (
                AuthenticatorInterface $AuthenticationTarget,
                array $params,
                ?bool &$authenticationExecuted
            ) use ($Authenticator): bool {
                self::assertSame($Authenticator, $AuthenticationTarget);
                self::assertSame([], $params);
                $authenticationExecuted = true;

                return true;
            });
        $Users->expects(self::once())
            ->method('login')
            ->willReturn($User);
        $Users->method('isAuth')->willReturn(false);

        $Config = $this->createMock(Config::class);
        $Config->method('get')
            ->willReturnCallback(static function (string $section, ?string $key = null): mixed {
                if ($section === 'auth_settings' && $key === 'secondary_frontend') {
                    return 1;
                }

                return false;
            });

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Users = $Users;
        QUI::$Conf = $Config;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];

        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_SECONDARY,
            ['PrimaryAuthenticator']
        );

        self::assertIsArray($result);
        self::assertTrue($result['loggedIn']);
        self::assertSame(1, $loginStartEvents);
        self::assertSame(1, $sessionValues['auth-primary']);
        self::assertSame(1, $sessionValues['auth-secondary']);
    }

    public function testCachedAuthenticationDoesNotRefreshEnrollmentAuthorization(): void
    {
        $authenticator = QUIQQER::class;
        $uid = 'test-user-uuid';
        $Session = new QUI\System\Console\Session();
        $Session->set('uid', $uid);
        $Session->set('username', 'test-user');
        $Session->set('auth', 1);
        $Session->set('auth-primary', 1);
        $Session->set('auth-secondary', 0);
        $Session->set('auth-' . $authenticator, 1);
        $Session->set(WebAuthn\Server::SESSION_ENROLLMENT, ['sentinel' => true]);

        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')->willReturn([]);

        $AuthHandler = $this->createMock(Handler::class);
        $AuthHandler->method('getGlobalFrontendAuthenticators')->willReturn([$authenticator]);

        $HandlerInstance = new ReflectionProperty(Handler::class, 'Instance');
        $HandlerInstance->setValue(null, $AuthHandler);

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uid);
        $User->method('getName')->willReturn('Test User');
        $User->method('getLang')->willReturn('en');

        $Users = $this->createMock(UserManager::class);
        $Users->expects(self::once())
            ->method('authenticate')
            ->willReturn(true);
        $Users->expects(self::once())
            ->method('login')
            ->willReturn($User);
        $Users->method('getUserBySession')->willReturn($User);
        $Users->method('isAuth')->willReturn(true);

        $Config = $this->createMock(Config::class);
        $Config->method('get')
            ->willReturnCallback(static function (string $section, ?string $key = null): mixed {
                if ($section === 'auth_settings' && $key === 'secondary_frontend') {
                    return 0;
                }

                return false;
            });

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Users = $Users;
        QUI::$Conf = $Config;
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 5) . '/admin/ajax/users/login.php';

        $registeredCallables = Ajax::getRegisteredCallables();
        $login = $registeredCallables['ajax_users_login']['callable'];
        $result = $login(
            $authenticator,
            [],
            SessionFailureCounter::STEP_PRIMARY,
            [$authenticator]
        );

        self::assertTrue($result['loggedIn']);
        self::assertSame(
            ['sentinel' => true],
            $Session->get(WebAuthn\Server::SESSION_ENROLLMENT)
        );
    }
}
