<?php

namespace QUI\Users;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

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
}
