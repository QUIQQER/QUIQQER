<?php

declare(strict_types=1);

namespace QUI\Users\Auth\WebAuthn;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Registration\WebAuthn\Registrar;
use QUI\System\Console\Session;
use QUI\Users\Manager as UserManager;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class BeginUserRegistrationSecurityTest extends TestCase
{
    public function testPublicOptionsEndpointDoesNotRevealUsernameExistence(): void
    {
        $Users = $this->createMock(UserManager::class);
        $Users->expects(self::never())->method('usernameExists');
        QUI::$Users = $Users;
        QUI::$Session = new Session();
        QUI::$Ajax = new Ajax();

        require dirname(__DIR__, 6) . '/admin/ajax/users/authenticator/webauthn/beginUserRegistration.php';

        $callables = Ajax::getRegisteredCallables();
        $beginRegistration = $callables['ajax_users_authenticator_webauthn_beginUserRegistration']['callable'];
        $result = $beginRegistration('existing-user', 'Existing User', 'First passkey');

        self::assertArrayHasKey('publicKey', $result);
        self::assertArrayHasKey('userHandle', $result);
    }

    public function testRegistrarStillRejectsAnExistingUsername(): void
    {
        $Users = $this->createMock(UserManager::class);
        $Users->expects(self::once())
            ->method('usernameExists')
            ->with('existing-user')
            ->willReturn(true);
        QUI::$Users = $Users;

        $Registrar = new Registrar();
        $Registrar->setAttribute('username', 'existing-user');

        $this->expectException(QUI\FrontendUsers\Exception::class);

        $Registrar->validate();
    }
}
