<?php

namespace QUI\Lock;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class SettingsAjaxTest extends TestCase
{
    #[DataProvider('actions')]
    public function testEndpointsRequireAnAdministratorAndSettingsPermission(string $action): void
    {
        QUI::$Ajax = new QUI\Ajax();
        require dirname(__DIR__, 4) . '/admin/ajax/system/settings/' . $action . 'ProcessLocks.php';
        $name = 'ajax_system_settings_' . $action . 'ProcessLocks';
        $permissions = (new \ReflectionProperty(QUI\Ajax::class, 'permissions'))->getValue();
        self::assertSame(['Permission::checkAdminUser', 'quiqqer.settings'], $permissions[$name]);

        QUI\Permissions\Permission::setUser(new QUI\Users\Nobody());
        $this->expectException(QUI\Permissions\Exception::class);
        QUI\Ajax::checkPermissions($name);
    }

    public static function actions(): array
    {
        return [['get'], ['save'], ['test']];
    }
}
