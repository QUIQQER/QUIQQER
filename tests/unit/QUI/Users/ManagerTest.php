<?php

namespace QUI\Users;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Config;
use QUI\Events\Manager as EventsManager;
use QUI\Interfaces\Users\User;
use QUI\Session as WebSession;
use QUI\System\Console\Session;
use QUI\Users\AuthenticatorInterface;
use ReflectionProperty;

class ManagerTest extends TestCase
{
    #[DataProvider('validUsernameProvider')]
    public function testCheckUsernameSignsAcceptsValidUsernames(string $username): void
    {
        $this->assertTrue(Manager::checkUsernameSigns($username));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function validUsernameProvider(): array
    {
        return [
            'plain username' => ['valid-user_123'],
            'email with ascii domain' => ['user.name+tag@example.com'],
            'email with idn domain' => ['user.name+tag@例子.广告']
        ];
    }

    #[DataProvider('invalidUsernameProvider')]
    public function testCheckUsernameSignsRejectsInvalidUsernames(string $username): void
    {
        $this->expectException(Exception::class);

        Manager::checkUsernameSigns($username);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidUsernameProvider(): array
    {
        return [
            'plain username with space' => ['plain name'],
            'email with unicode local part' => ['用户@例子.广告'],
            'email with missing local part' => ['@example.com'],
            'email with missing domain' => ['user@'],
            'email with multiple at signs' => ['user@@example.com']
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateReportsCachedAuthenticationAsNotExecuted(): void
    {
        $Authenticator = $this->createMock(AuthenticatorInterface::class);
        $Authenticator->expects(self::never())->method('auth');

        $Session = new Session();
        $Session->set('username', 'test-user');
        $Session->set('uid', 'test-user-uuid');
        $Session->set('auth-' . $Authenticator::class, 1);
        QUI::$Session = $Session;

        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')->willReturn([]);
        QUI::$Events = $Events;

        $authenticationExecuted = null;

        self::assertTrue((new Manager())->authenticate($Authenticator, [], $authenticationExecuted));
        self::assertFalse($authenticationExecuted);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateReportsAuthenticatorExecution(): void
    {
        $User = $this->createMock(User::class);
        $User->method('getUsername')->willReturn('test-user');
        $User->method('getUUID')->willReturn('test-user-uuid');

        $Authenticator = $this->createMock(AuthenticatorInterface::class);
        $Authenticator->expects(self::once())->method('auth')->with([]);
        $Authenticator->method('getUser')->willReturn($User);

        QUI::$Session = new Session();

        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')->willReturn([]);
        QUI::$Events = $Events;

        $authenticationExecuted = null;

        self::assertTrue((new Manager())->authenticate($Authenticator, [], $authenticationExecuted));
        self::assertTrue($authenticationExecuted);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateChecksPersistentThrottleForAuthenticatorResolvedUser(): void
    {
        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn('test-user-uuid');

        $Authenticator = $this->createMock(AuthenticatorInterface::class);
        $Authenticator->expects(self::once())->method('getUser')->willReturn($User);
        $Authenticator->expects(self::never())->method('auth');

        QUI::$Session = new Session();

        $Events = $this->createMock(EventsManager::class);
        $Events->expects(self::once())
            ->method('fireEvent')
            ->with('userAuthenticatorLoginStart', ['test-user-uuid', $Authenticator])
            ->willThrowException(new Exception('Login temporarily locked', 429));
        QUI::$Events = $Events;

        $this->expectException(Exception::class);
        $this->expectExceptionCode(429);

        (new Manager())->authenticate($Authenticator);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAuthenticateReturnsUniformFailureForDifferentAuthenticationErrors(): void
    {
        QUI::$Session = new Session();

        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')->willReturn([]);
        QUI::$Events = $Events;

        $responses = [];
        $authenticationErrors = [
            new Exception('User not found', 404, ['account' => 'missing']),
            new Exception('Wrong password', 401, ['account' => 'existing'])
        ];

        foreach ($authenticationErrors as $AuthenticationError) {
            $User = $this->createMock(User::class);
            $User->method('getUUID')->willReturn('test-user-uuid');

            $Authenticator = $this->createMock(AuthenticatorInterface::class);
            $Authenticator->method('getUser')->willReturn($User);
            $Authenticator->method('auth')->willThrowException($AuthenticationError);

            try {
                (new Manager())->authenticate($Authenticator);
                self::fail('Authentication errors must be converted to UserAuthException.');
            } catch (UserAuthException $Exception) {
                $responses[] = [
                    'message' => $Exception->getMessage(),
                    'code' => $Exception->getCode(),
                    'context' => $Exception->getContext(),
                    'reason' => $Exception->getAttribute('reason')
                ];
            }
        }

        self::assertCount(2, $responses);
        self::assertSame($responses[0], $responses[1]);
        self::assertSame(401, $responses[0]['code']);
        self::assertSame(
            ['quiqqer/core', 'exception.login.fail'],
            $responses[0]['context']['locale'] ?? null
        );
        self::assertSame(Manager::AUTH_ERROR_AUTH_ERROR, $responses[0]['reason']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testLoginRegeneratesSessionBeforeStoringAuthenticatedState(): void
    {
        $Connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true
        ]);
        $table = Manager::table();
        $quotedTable = $Connection->getDatabasePlatform()->quoteIdentifier($table);

        $Connection->executeStatement(
            'CREATE TABLE ' . $quotedTable . ' ('
            . 'id INTEGER PRIMARY KEY, '
            . 'uuid VARCHAR(36) UNIQUE, '
            . 'expire VARCHAR(19), '
            . 'secHash VARCHAR(32), '
            . 'active INTEGER, '
            . 'lastvisit INTEGER, '
            . 'user_agent VARCHAR(255)'
            . ')'
        );

        $userId = 'test-user-uuid';
        $Connection->insert($table, [
            'id' => 42,
            'uuid' => $userId,
            'expire' => null,
            'secHash' => '',
            'active' => 1,
            'lastvisit' => 0,
            'user_agent' => ''
        ]);

        $ConnectionProperty = new ReflectionProperty(QUI::class, 'QueryBuilder');
        $ConnectionProperty->setValue(null, $Connection);

        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(true);
        $User->expects(self::once())->method('refresh');

        $sessionValues = [
            'auth' => 0,
            'auth-primary' => 1,
            'uid' => $userId,
            'inAuthentication' => 1
        ];
        $regenerated = false;

        $Session = $this->createMock(WebSession::class);
        $Session->method('get')
            ->willReturnCallback(static function (string $key) use (&$sessionValues): mixed {
                return $sessionValues[$key] ?? false;
            });
        $Session->expects(self::once())
            ->method('regenerate')
            ->willReturnCallback(static function () use (&$regenerated): bool {
                $regenerated = true;

                return true;
            });
        $Session->method('remove')
            ->willReturnCallback(static function (string $key) use (&$sessionValues): void {
                unset($sessionValues[$key]);
            });
        $Session->method('set')
            ->willReturnCallback(static function (string $key, mixed $value) use (&$sessionValues, &$regenerated): void {
                if (in_array($key, ['auth', 'uid', 'secHash'], true)) {
                    self::assertTrue($regenerated);
                }

                $sessionValues[$key] = $value;
            });

        $Events = $this->createMock(EventsManager::class);
        $Events->method('fireEvent')->willReturn([]);

        $Config = $this->createMock(Config::class);
        $Config->method('get')->willReturn(false);

        $Manager = new class ($User) extends Manager {
            public function __construct(private readonly User $User)
            {
            }

            public function get(int | string $id): User
            {
                return $this->User;
            }

            public function getSecHash(): string
            {
                return 'test-security-hash';
            }
        };

        QUI::$Session = $Session;
        QUI::$Events = $Events;
        QUI::$Users = $Manager;
        QUI::$Conf = $Config;

        self::assertSame($User, $Manager->login());
        self::assertSame(1, $sessionValues['auth']);
        self::assertSame($userId, $sessionValues['uid']);
        self::assertSame('test-security-hash', $sessionValues['secHash']);
        self::assertArrayNotHasKey('inAuthentication', $sessionValues);
    }
}
