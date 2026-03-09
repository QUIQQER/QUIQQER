<?php

namespace QUI\Security;

use PHPUnit\Framework\TestCase;

class PasswordTest extends TestCase
{
    public function testGenerateRandomUsesDefaultLength(): void
    {
        $password = Password::generateRandom();

        $this->assertSame(16, strlen($password));
    }

    public function testGenerateRandomUsesProvidedLength(): void
    {
        $password = Password::generateRandom(32);

        $this->assertSame(32, strlen($password));
    }

    public function testGenerateRandomReturnsEmptyStringForNonPositiveLength(): void
    {
        $this->assertSame('', Password::generateRandom(0));
        $this->assertSame('', Password::generateRandom(-5));
    }

    public function testGenerateRandomUsesOnlyAllowedCharacters(): void
    {
        $password = Password::generateRandom(256);

        $this->assertSame(1, preg_match('/^[a-zA-Z_-]+$/', $password));
    }
}
