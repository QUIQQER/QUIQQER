<?php

namespace QUI;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Permissions\Permission;
use QUI\Users\Manager as UserManager;
use QUI\Users\Nobody;
use QUI\Users\User;
use ReflectionProperty;

require_once __DIR__ . '/AccessibleSetup.php';

class SetupTest extends TestCase
{
    private ?UserManager $previousUserManager;
    private ?UserInterface $previousPermissionUser;

    protected function setUp(): void
    {
        $UserProperty = new ReflectionProperty(Permission::class, 'User');

        $this->previousUserManager = QUI::$Users;
        $this->previousPermissionUser = $UserProperty->getValue();
    }

    protected function tearDown(): void
    {
        $UserProperty = new ReflectionProperty(Permission::class, 'User');
        $UserProperty->setValue(null, $this->previousPermissionUser);

        QUI::$Users = $this->previousUserManager;
    }

    public function testSetupPermissionUsesConfiguredPermissionUserForInstallerSession(): void
    {
        $SessionUser = $this->createMock(Nobody::class);
        $ConfiguredUser = $this->createSuperUser();
        $Users = $this->createMock(UserManager::class);

        $Users->method('getUserBySession')->willReturn($SessionUser);
        $Users->method('isSystemUser')->with($SessionUser)->willReturn(false);

        QUI::$Users = $Users;
        Permission::setUser($ConfiguredUser);

        AccessibleSetup::checkSetupPermission();

        $this->addToAssertionCount(1);
    }

    private function createSuperUser(): User&MockObject
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn(true);

        return $User;
    }
}
