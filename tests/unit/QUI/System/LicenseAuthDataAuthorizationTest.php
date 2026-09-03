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
final class LicenseAuthDataAuthorizationTest extends TestCase
{
    private const AJAX_FUNCTION = 'ajax_licenseKey_getAuthData';

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
        require dirname(__DIR__, 4) . '/admin/ajax/licenseKey/getAuthData.php';
    }

    protected function tearDown(): void
    {
        $permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $permissionUserProperty->setValue(null, $this->previousPermissionUser);

        QUI::$Ajax = $this->previousAjax;
        QUI::$Rights = $this->previousPermissionManager;

        parent::tearDown();
    }

    public function testEndpointRequiresBackendAndUpdatePermissions(): void
    {
        $permissionsProperty = new ReflectionProperty(Ajax::class, 'permissions');
        $permissions = $permissionsProperty->getValue();

        self::assertSame(
            [
                'Permission::checkAdminUser',
                'quiqqer.system.update'
            ],
            $permissions[self::AJAX_FUNCTION] ?? null
        );
    }

    public function testBackendUserWithoutUpdatePermissionIsRejected(): void
    {
        $this->setActor(false, [
            'quiqqer.admin' => true
        ]);

        $this->expectException(PermissionException::class);

        Ajax::checkPermissions(self::AJAX_FUNCTION);
    }

    public function testUpdateManagerWithoutBackendPermissionIsRejected(): void
    {
        $this->setActor(false, [
            'quiqqer.system.update' => true
        ]);

        $this->expectException(PermissionException::class);

        Ajax::checkPermissions(self::AJAX_FUNCTION);
    }

    public function testBackendUpdateManagerIsAllowed(): void
    {
        $this->setActor(false, [
            'quiqqer.admin' => true,
            'quiqqer.system.update' => true
        ]);

        Ajax::checkPermissions(self::AJAX_FUNCTION);

        $this->addToAssertionCount(1);
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
