<?php

declare(strict_types=1);

namespace QUITests\QUI\System;

use PHPUnit\Framework\TestCase;
use QUI;
use QUI\System\Console;
use QUI\Users\User;
use Ramsey\Uuid\Uuid;
use Throwable;

use function array_merge;
use function bin2hex;
use function fclose;
use function fwrite;
use function implode;
use function is_resource;
use function proc_close;
use function proc_open;
use function random_bytes;

use const CMS_DIR;
use const PHP_BINARY;
use const PHP_EOL;

final class ConsolePasswordResetIntegrationTest extends TestCase
{
    private ?User $User = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $username = 'console-password-reset-' . bin2hex(random_bytes(8));
        $SystemUser = QUI::getUsers()->getSystemUser();
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $SystemUser);

        self::assertInstanceOf(User::class, $User);
        $User->setPassword(bin2hex(random_bytes(18)), $SystemUser);
        $User->activate('', $SystemUser);
        $this->User = $User;
    }

    protected function tearDown(): void
    {
        if ($this->User !== null) {
            $this->User->delete(QUI::getUsers()->getSystemUser());
        }

        parent::tearDown();
    }

    public function testPasswordResetProcessStatusesAndPasswordChanges(): void
    {
        $User = $this->User;
        self::assertNotNull($User);

        $passwordBeforeUsernameReset = bin2hex(random_bytes(18));
        $User->setPassword($passwordBeforeUsernameReset, QUI::getUsers()->getSystemUser());

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_SUCCESS,
            $this->runPasswordReset([$User->getUsername(), 'y', 'y'])
        );

        $User->refresh();
        self::assertFalse($User->checkPassword($passwordBeforeUsernameReset));

        $passwordBeforeUuidReset = bin2hex(random_bytes(18));
        $User->setPassword($passwordBeforeUuidReset, QUI::getUsers()->getSystemUser());

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_SUCCESS,
            $this->runPasswordReset([(string)$User->getUUID(), 'y', 'y'])
        );

        $User->refresh();
        self::assertFalse($User->checkPassword($passwordBeforeUuidReset));

        $passwordBeforeUnknownUser = bin2hex(random_bytes(18));
        $User->setPassword($passwordBeforeUnknownUser, QUI::getUsers()->getSystemUser());

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_USER_NOT_FOUND,
            $this->runPasswordReset(['missing-user-' . bin2hex(random_bytes(8))])
        );

        $User->refresh();
        self::assertTrue($User->checkPassword($passwordBeforeUnknownUser));

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_USER_NOT_FOUND,
            $this->runPasswordReset([Uuid::uuid4()->toString()])
        );

        $User->refresh();
        self::assertTrue($User->checkPassword($passwordBeforeUnknownUser));

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_CANCELLED,
            $this->runPasswordReset([$User->getUsername(), 'n'])
        );

        $User->refresh();
        self::assertTrue($User->checkPassword($passwordBeforeUnknownUser));

        self::assertSame(
            Console::PASSWORD_RESET_EXIT_CANCELLED,
            $this->runPasswordReset([$User->getUsername(), 'y', 'n'])
        );

        $User->refresh();
        self::assertTrue($User->checkPassword($passwordBeforeUnknownUser));
    }

    /**
     * @param list<string> $inputs
     */
    private function runPasswordReset(array $inputs): int
    {
        $process = proc_open(
            [
                PHP_BINARY,
                CMS_DIR . 'console',
                'password-reset',
                '--noLogo',
                '--ignore-file-permissions'
            ],
            [
                0 => ['pipe', 'r'],
                1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w']
            ],
            $pipes,
            CMS_DIR
        );

        self::assertTrue(is_resource($process));
        fwrite($pipes[0], implode(PHP_EOL, array_merge($inputs, [''])));
        fclose($pipes[0]);

        return proc_close($process);
    }

    private function skipIfDatabaseIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            QUI::getUsers()->getSystemUser();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database fixtures are unavailable: ' . $Exception->getMessage());
        }
    }
}
