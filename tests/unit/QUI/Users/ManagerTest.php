<?php

namespace QUI\Users;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Events\Manager as EventsManager;
use QUI\Interfaces\Users\User;
use QUI\System\Console\Session;
use QUI\Users\AuthenticatorInterface;

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
}
