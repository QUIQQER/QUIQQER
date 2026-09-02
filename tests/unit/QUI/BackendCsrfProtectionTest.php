<?php

namespace QUI;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI\Security\CsrfToken;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BackendCsrfProtectionTest extends TestCase
{
    public function testAuthenticatedBackendRequestWithoutTokenIsRejected(): void
    {
        $this->enableAuthenticatedBackendRequest();
        $called = false;
        $Ajax = new Ajax();
        $Ajax::registerFunction('test_backend_csrf', static function () use (&$called): void {
            $called = true;
        });

        $result = $Ajax->callRequestFunction('test_backend_csrf');

        self::assertFalse($called);
        self::assertSame(403, $result['Exception']['code']);
    }

    public function testAuthenticatedBackendRequestWithTokenIsAccepted(): void
    {
        $this->enableAuthenticatedBackendRequest();
        $Ajax = new Ajax();
        $Ajax::registerFunction('test_backend_csrf', static fn(): bool => true);

        $result = $Ajax->callRequestFunction(
            'test_backend_csrf',
            ['_csrf' => CsrfToken::get()]
        );

        self::assertTrue($result['result']);
    }

    public function testNestedBackendCallUsesValidatedOuterRequest(): void
    {
        $this->enableAuthenticatedBackendRequest();
        $Ajax = new Ajax();
        $Ajax::registerFunction('test_backend_csrf_inner', static fn(): bool => true);
        $Ajax::registerFunction(
            'test_backend_csrf_outer',
            static fn(): array => $Ajax->callRequestFunction('test_backend_csrf_inner')
        );

        $result = $Ajax->callRequestFunction(
            'test_backend_csrf_outer',
            ['_csrf' => CsrfToken::get()]
        );

        self::assertTrue($result['result']['result']);
    }

    public function testUnauthenticatedBackendRequestDoesNotRequireToken(): void
    {
        define('QUIQQER_BACKEND', true);
        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $Users->getNobody());

        $Ajax = new Ajax();
        $Ajax::registerFunction('test_backend_login', static fn(): bool => true);

        $result = $Ajax->callRequestFunction('test_backend_login');

        self::assertTrue($result['result']);
    }

    public function testFrontendRequestDoesNotRequireToken(): void
    {
        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $Users->getSystemUser());

        $Ajax = new Ajax();
        $Ajax::registerFunction('test_frontend_request', static fn(): bool => true);

        $result = $Ajax->callRequestFunction('test_frontend_request');

        self::assertTrue($result['result']);
    }

    public function testExplicitlyProtectedSharedDispatcherRequestWithoutTokenIsRejected(): void
    {
        $Ajax = new Ajax();
        $called = false;
        $Ajax::registerCsrfProtectedFunction(
            'test_shared_dispatcher_csrf',
            static function () use (&$called): void {
                $called = true;
            }
        );

        $result = $Ajax->callRequestFunction('test_shared_dispatcher_csrf');

        self::assertFalse($called);
        self::assertSame(403, $result['Exception']['code']);
    }

    public function testExplicitlyProtectedSharedDispatcherRequestWithTokenIsAccepted(): void
    {
        $Ajax = new Ajax();
        $Ajax::registerCsrfProtectedFunction(
            'test_shared_dispatcher_csrf',
            static fn(): bool => true
        );

        $result = $Ajax->callRequestFunction(
            'test_shared_dispatcher_csrf',
            ['_csrf' => CsrfToken::get()]
        );

        self::assertTrue($result['result']);
    }

    private function enableAuthenticatedBackendRequest(): void
    {
        define('QUIQQER_BACKEND', true);
        $Users = \QUI::getUsers();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setValue($Users, $Users->getSystemUser());
    }
}
