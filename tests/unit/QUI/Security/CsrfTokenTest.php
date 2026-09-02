<?php

namespace QUI\Security;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI\Exception;
use QUI\Session;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class CsrfTokenTest extends TestCase
{
    public function testGeneratedTokenIsAcceptedForCurrentSession(): void
    {
        $token = CsrfToken::get();

        CsrfToken::assertValid($token);

        self::assertSame($token, CsrfToken::get());
    }

    public function testAttackerControlledTokenIsRejected(): void
    {
        CsrfToken::get();

        $this->expectException(Exception::class);
        $this->expectExceptionCode(403);

        CsrfToken::assertValid('attacker-controlled');
    }

    public function testTokenCannotBeAccessedThroughClientSessionApi(): void
    {
        self::assertFalse(Session::isClientSessionKeyAllowed(CsrfToken::SESSION_KEY));
    }
}
