<?php

declare(strict_types=1);

namespace QUITests\QUI\System;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\System\Console;
use QUI\Users\Manager;
use QUI\Users\SystemUser;
use RuntimeException;

use function bin2hex;
use function implode;
use function random_bytes;

require_once __DIR__ . '/PasswordResetTestConsole.php';

final class ConsolePasswordResetTest extends TestCase
{
    private ?Manager $PreviousUsers;

    protected function setUp(): void
    {
        $this->PreviousUsers = QUI::$Users;
    }

    protected function tearDown(): void
    {
        QUI::$Users = $this->PreviousUsers;
    }

    public function testExistingUserCanBeResetByUsername(): void
    {
        $uuid = '9c506425-4d2f-46bb-8901-8a6dd718a6d1';
        [$Users, $User, $SystemUser] = $this->createUserManager('alice', $uuid);

        $Users->expects(self::once())->method('getUserByName')->with('alice')->willReturn($User);
        $Users->expects(self::never())->method('get');
        $Users->method('getSystemUser')->willReturn($SystemUser);
        $this->expectPasswordChange($User, $SystemUser);

        QUI::$Users = $Users;
        $Console = new PasswordResetTestConsole(['alice', 'y', 'y']);

        self::assertSame(Console::PASSWORD_RESET_EXIT_SUCCESS, $Console->runPasswordReset());

        $output = implode("\n", $Console->output);
        self::assertStringContainsString('alice', $output);
        self::assertStringContainsString($uuid, $output);
    }

    public function testExistingUserCanBeResetByUuid(): void
    {
        $uuid = '9c506425-4d2f-46bb-8901-8a6dd718a6d1';
        [$Users, $User, $SystemUser] = $this->createUserManager('alice', $uuid);

        $Users->expects(self::once())->method('get')->with($uuid)->willReturn($User);
        $Users->expects(self::never())->method('getUserByName');
        $Users->method('getSystemUser')->willReturn($SystemUser);
        $this->expectPasswordChange($User, $SystemUser);

        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_SUCCESS,
            (new PasswordResetTestConsole([$uuid, 'y', 'y']))->runPasswordReset()
        );
    }

    public function testUnknownUserReturnsDocumentedStatus(): void
    {
        $Users = $this->createMock(Manager::class);
        $Users->method('getUserByName')->willThrowException(new QUI\Users\Exception('not found', 404));
        $Users->expects(self::never())->method('getSystemUser');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_USER_NOT_FOUND,
            (new PasswordResetTestConsole(['missing-user']))->runPasswordReset()
        );
    }

    public function testUnknownUuidReturnsDocumentedStatus(): void
    {
        $uuid = '9c506425-4d2f-46bb-8901-8a6dd718a6d1';
        $Users = $this->createMock(Manager::class);
        $Users->method('get')->with($uuid)->willThrowException(new QUI\Users\Exception('not found', 404));
        $Users->expects(self::never())->method('getUserByName');
        $Users->expects(self::never())->method('getSystemUser');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_USER_NOT_FOUND,
            (new PasswordResetTestConsole([$uuid]))->runPasswordReset()
        );
    }

    public function testEmptyIdentifierReturnsCancelledStatus(): void
    {
        $Users = $this->createMock(Manager::class);
        $Users->expects(self::never())->method('getUserByName');
        $Users->expects(self::never())->method('get');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_CANCELLED,
            (new PasswordResetTestConsole(['']))->runPasswordReset()
        );
    }

    public function testDecliningFirstConfirmationDoesNotChangePassword(): void
    {
        [$Users, $User] = $this->createUserManager('alice', '9c506425-4d2f-46bb-8901-8a6dd718a6d1');
        $Users->method('getUserByName')->willReturn($User);
        $Users->expects(self::never())->method('getSystemUser');
        $User->expects(self::never())->method('setPassword');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_CANCELLED,
            (new PasswordResetTestConsole(['alice', 'n']))->runPasswordReset()
        );
    }

    public function testDecliningSecondConfirmationDoesNotChangePassword(): void
    {
        [$Users, $User] = $this->createUserManager('alice', '9c506425-4d2f-46bb-8901-8a6dd718a6d1');
        $Users->method('getUserByName')->willReturn($User);
        $Users->expects(self::never())->method('getSystemUser');
        $User->expects(self::never())->method('setPassword');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_CANCELLED,
            (new PasswordResetTestConsole(['alice', 'y', 'n']))->runPasswordReset()
        );
    }

    public function testUnexpectedLookupFailureReturnsRuntimeStatus(): void
    {
        $Users = $this->createMock(Manager::class);
        $Users->method('getUserByName')->willThrowException(new RuntimeException('lookup failed'));
        $Users->expects(self::never())->method('getSystemUser');
        QUI::$Users = $Users;

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_RUNTIME_FAILURE,
            (new PasswordResetTestConsole(['alice']))->runPasswordReset()
        );
    }

    public function testPasswordUpdateFailureReturnsRuntimeStatusWithoutPasswordInErrorOutput(): void
    {
        [$Users, $User, $SystemUser] = $this->createUserManager(
            'alice',
            '9c506425-4d2f-46bb-8901-8a6dd718a6d1'
        );
        $Users->method('getUserByName')->willReturn($User);
        $Users->method('getSystemUser')->willReturn($SystemUser);
        $User->method('setPassword')->willThrowException(new RuntimeException('update failed'));
        QUI::$Users = $Users;

        $generatedPassword = bin2hex(random_bytes(18));
        $Console = new PasswordResetTestConsole(['alice', 'y', 'y'], $generatedPassword);

        self::assertSame(Console::PASSWORD_RESET_EXIT_RUNTIME_FAILURE, $Console->runPasswordReset());
        self::assertNotEmpty($Console->output);

        foreach ($Console->output as $output) {
            self::assertStringNotContainsString($generatedPassword, $output);
        }
    }

    /**
     * @return array{MockObject&Manager, MockObject&User, MockObject&SystemUser}
     */
    private function createUserManager(string $username, string $uuid): array
    {
        $Users = $this->createMock(Manager::class);
        $User = $this->createMock(User::class);
        $SystemUser = $this->createMock(SystemUser::class);

        $User->method('getUsername')->willReturn($username);
        $User->method('getUUID')->willReturn($uuid);

        return [$Users, $User, $SystemUser];
    }

    private function expectPasswordChange(MockObject&User $User, MockObject&SystemUser $SystemUser): void
    {
        $User->expects(self::once())->method('setPassword')->with(
            self::callback(static fn(string $password): bool => $password !== ''),
            $SystemUser
        );
    }
}
