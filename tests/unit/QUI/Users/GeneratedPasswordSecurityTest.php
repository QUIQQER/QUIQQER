<?php

declare(strict_types=1);

namespace QUI\Users;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use ReflectionProperty;

use function file_get_contents;
use function preg_match;
use function strlen;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class GeneratedPasswordSecurityTest extends TestCase
{
    private const AJAX_FUNCTION = 'ajax_user_generateRandomPassword';

    protected function setUp(): void
    {
        parent::setUp();

        QUI::$Ajax = new Ajax();
        require dirname(__DIR__, 4) . '/admin/ajax/user/generateRandomPassword.php';
    }

    public function testEndpointUsesSecureCoreGenerator(): void
    {
        $callable = Ajax::getRegisteredCallables()[self::AJAX_FUNCTION]['callable'];
        $password = $callable();

        self::assertSame(16, strlen($password));
        self::assertSame(1, preg_match('/^[a-zA-Z_-]+$/', $password));

        $passwordSource = (string)file_get_contents(
            dirname(__DIR__, 4) . '/src/QUI/Security/Password.php'
        );

        self::assertStringNotContainsString('mt_rand(', $passwordSource);
    }

    public function testEndpointRequiresUserEditPermission(): void
    {
        $permissionsProperty = new ReflectionProperty(Ajax::class, 'permissions');
        $permissions = $permissionsProperty->getValue();

        self::assertSame(
            ['Permission::checkAdminUser', 'quiqqer.admin.users.edit'],
            $permissions[self::AJAX_FUNCTION] ?? null
        );
    }

    public function testAdministrativeControlsUseServerGeneratedPasswords(): void
    {
        $coreDirectory = dirname(__DIR__, 4);
        $userPanelSource = (string)file_get_contents(
            $coreDirectory . '/bin/QUI/controls/users/User.js'
        );
        $sendPasswordSource = (string)file_get_contents(
            $coreDirectory . '/bin/QUI/controls/users/password/send/SendPassword.js'
        );

        self::assertStringContainsString(
            "QUIAjax.get('ajax_user_generateRandomPassword'",
            $userPanelSource
        );
        self::assertStringContainsString(
            "QUIAjax.get('ajax_user_generateRandomPassword'",
            $sendPasswordSource
        );
        self::assertStringNotContainsString('Math.random()', $userPanelSource);
        self::assertStringNotContainsString('Math.random()', $sendPasswordSource);
        self::assertStringContainsString(
            "QUIAjax.post('ajax_user_setAndSendPassword'",
            $sendPasswordSource
        );
    }
}
