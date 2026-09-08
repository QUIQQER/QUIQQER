<?php

declare(strict_types=1);

namespace QUITests\QUI\Users\Auth;

use Closure;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\System\Console\Session;

class LoginSessionCheckTest extends TestCase
{
    private mixed $previousSession;
    private mixed $previousAjax;
    private array $previousServer;
    private Closure $check;

    protected function setUp(): void
    {
        $this->previousSession = QUI::$Session;
        $this->previousAjax = QUI::$Ajax;
        $this->previousServer = $_SERVER;
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'same-origin';
        unset($_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_REFERER']);
        QUI::$Session = new Session();
        QUI::$Ajax = new Ajax();
        require dirname(__DIR__, 5) . '/admin/ajax/users/checkSession.php';
        $this->check = Ajax::getRegisteredCallables()['ajax_users_checkSession']['callable'];
    }

    protected function tearDown(): void
    {
        QUI::$Session = $this->previousSession;
        QUI::$Ajax = $this->previousAjax;
        $_SERVER = $this->previousServer;
    }

    public function testSameSessionIsConfirmedWithoutChangingAuthentication(): void
    {
        QUI::getSession()->set('auth-primary', 1);
        QUI::getSession()->set('uid', 'test-user');
        $token = ($this->check)();
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $token);
        self::assertTrue(($this->check)($token));
        self::assertSame(1, QUI::getSession()->get('auth-primary'));
        self::assertSame('test-user', QUI::getSession()->get('uid'));
    }

    public function testMissingSessionCookieCannotConfirmThePreviousSession(): void
    {
        $token = ($this->check)();
        QUI::$Session = new Session();
        self::assertFalse(($this->check)($token));
        self::assertFalse(QUI::getSession()->get('quiqqer.security.loginSessionCheck'));
    }

    public function testConcurrentChecksKeepTheirDiagnosticToken(): void
    {
        $first = ($this->check)();
        $second = ($this->check)();
        self::assertSame($first, $second);
        self::assertTrue(($this->check)($first));
        self::assertTrue(($this->check)($second));
    }

    public function testInvalidTokensCannotConfirmASession(): void
    {
        ($this->check)();
        self::assertFalse(($this->check)(''));
        self::assertFalse(($this->check)('incorrect'));
        self::assertFalse(($this->check)([]));
    }

    public function testDiagnosticValueIsProtectedFromTheGenericSessionApi(): void
    {
        self::assertFalse(QUI\Session::isClientSessionKeyAllowed('quiqqer.security.loginSessionCheck'));
    }

    public function testCrossOriginRequestsAreRejectedBeforeCreatingDiagnosticState(): void
    {
        $_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
        $this->expectException(QUI\Exception::class);
        $this->expectExceptionCode(403);
        ($this->check)();
    }
}
