<?php

declare(strict_types=1);

namespace QUI\System;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Permissions\Exception as PermissionException;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Users\User;
use ReflectionProperty;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MemcachedTestAuthorizationTest extends TestCase
{
    private const AJAX_FUNCTION = 'ajax_settings_memcachedTest';

    private mixed $previousAjax;
    private mixed $previousPermissionManager;
    private mixed $previousPermissionUser;

    protected function setUp(): void
    {
        parent::setUp();

        $permissionUserProperty = new ReflectionProperty(Permission::class, 'User');

        $this->previousAjax = QUI::$Ajax;
        $this->previousPermissionManager = QUI::$Rights;
        $this->previousPermissionUser = $permissionUserProperty->getValue();

        QUI::$Ajax = new Ajax();
        require dirname(__DIR__, 4) . '/admin/ajax/settings/memcachedTest.php';
    }

    protected function tearDown(): void
    {
        $permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $permissionUserProperty->setValue(null, $this->previousPermissionUser);

        QUI::$Ajax = $this->previousAjax;
        QUI::$Rights = $this->previousPermissionManager;

        parent::tearDown();
    }

    public function testEndpointRequiresSuperUserPermission(): void
    {
        $permissionsProperty = new ReflectionProperty(Ajax::class, 'permissions');
        $permissions = $permissionsProperty->getValue();

        self::assertSame(
            'Permission::checkSU',
            $permissions[self::AJAX_FUNCTION] ?? null
        );
    }

    public function testBackendSettingsManagerIsRejected(): void
    {
        $this->setActor(false, [
            'quiqqer.admin' => true,
            'quiqqer.settings' => true
        ]);

        Permission::checkAdminUser();
        self::assertTrue(Permission::checkPermission('quiqqer.settings'));

        $this->expectException(PermissionException::class);

        Ajax::checkPermissions(self::AJAX_FUNCTION);
    }

    public function testSuperUserIsAllowed(): void
    {
        $this->setActor(true, []);

        Ajax::checkPermissions(self::AJAX_FUNCTION);

        $this->addToAssertionCount(1);
    }

    /**
     * @param array<string, bool> $permissions
     */
    private function setActor(bool $isSuperUser, array $permissions): void
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn($isSuperUser);
        $User->method('getGroups')->willReturn([]);

        $PermissionManager = $this->createMock(PermissionManager::class);
        $PermissionManager->method('getPermissions')->willReturn($permissions);

        Permission::setUser($User);
        QUI::$Rights = $PermissionManager;
    }
}
