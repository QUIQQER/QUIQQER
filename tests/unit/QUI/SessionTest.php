<?php

namespace QUI;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\Users\Auth\VerifiedMail2FA;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\HttpFoundation\Session\Session as SymfonySession;

class SessionTest extends TestCase
{
    public function testRegenerateInvalidatesPreviousSession(): void
    {
        $SymfonySession = $this->createMock(SymfonySession::class);
        $SymfonySession->expects(self::once())
            ->method('migrate')
            ->with(true)
            ->willReturn(true);

        $Session = (new ReflectionClass(Session::class))->newInstanceWithoutConstructor();
        $SessionProperty = new ReflectionProperty(Session::class, 'Session');
        $SessionProperty->setValue($Session, $SymfonySession);

        self::assertTrue($Session->regenerate());
    }

    #[DataProvider('protectedClientSessionKeyProvider')]
    public function testProtectedClientSessionKeysAreRejected(string $key): void
    {
        self::assertFalse(Session::isClientSessionKeyAllowed($key));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function protectedClientSessionKeyProvider(): iterable
    {
        yield 'user id' => ['uid'];
        yield 'authentication root' => ['auth'];
        yield 'primary authentication' => ['auth-primary'];
        yield 'authentication prefix' => ['authentication-state'];
        yield 'authentication in progress' => ['inAuthentication'];
        yield 'security hash' => ['secHash'];
        yield 'master login user' => ['session_master_user_id'];
        yield 'master login state' => ['session_master_state'];
        yield 'master login log' => ['session_log_id'];
        yield 'master login log state' => ['session_log_state'];
        yield 'mail MFA login code' => [VerifiedMail2FA::USER_CODE_ATTRIBUTE];
        yield 'mail MFA verification code' => [VerifiedMail2FA::USER_CODE_VERIFYING_ATTRIBUTE];
    }

    #[DataProvider('allowedClientSessionKeyProvider')]
    public function testRegularClientSessionKeysRemainAllowed(string $key): void
    {
        self::assertTrue(Session::isClientSessionKeyAllowed($key));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function allowedClientSessionKeyProvider(): iterable
    {
        yield 'product view' => ['productView'];
        yield 'product price visibility' => ['QUIQQER_PRODUCTS_HIDE_PRICE'];
        yield 'ERP B2B status' => ['quiqqer.erp.b2b.status'];
        yield 'user language' => ['quiqqer-user-language'];
        yield 'package state' => ['package.custom.state'];
    }
}
