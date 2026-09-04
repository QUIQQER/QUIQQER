<?php

declare(strict_types=1);

namespace QUI\Editor;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Permissions\Exception as PermissionException;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Users\Manager as UserManager;

use function bin2hex;
use function dirname;
use function file_exists;
use function random_bytes;
use function rtrim;
use function unlink;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ManagerAddToolbarSecurityTest extends TestCase
{
    public function testDeniedPermissionStopsBeforeToolbarCreation(): void
    {
        $toolbar = 'denied-' . bin2hex(random_bytes(8));
        $file = Manager::getToolbarsPath() . $toolbar . '.xml';

        self::assertFileDoesNotExist($file);
        $this->setActor(false);

        try {
            Manager::addToolbar($toolbar);
            self::fail('Toolbar creation continued after denied permission.');
        } catch (PermissionException) {
            self::assertFileDoesNotExist($file);
        } finally {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    public function testAuthorizedTraversalIsRejected(): void
    {
        $toolbar = 'outside-' . bin2hex(random_bytes(8));
        $toolbarDirectory = rtrim(Manager::getToolbarsPath(), '/\\');
        $outsideFile = dirname($toolbarDirectory) . '/' . $toolbar . '.xml';

        self::assertFileDoesNotExist($outsideFile);
        $this->setActor(true);

        try {
            Manager::addToolbar('../' . $toolbar);
            self::fail('Toolbar creation accepted a path outside the toolbar directory.');
        } catch (QUI\Exception $Exception) {
            self::assertSame(400, $Exception->getCode());
            self::assertFileDoesNotExist($outsideFile);
        } finally {
            if (file_exists($outsideFile)) {
                unlink($outsideFile);
            }
        }
    }

    private function setActor(bool $isSuperUser): void
    {
        $User = $this->createMock(User::class);
        $User->method('isSU')->willReturn($isSuperUser);
        $User->method('getGroups')->willReturn([]);

        Permission::setUser($User);

        if ($isSuperUser) {
            return;
        }

        $Users = $this->createMock(UserManager::class);
        $Users->method('isSystemUser')->willReturn(false);
        QUI::$Users = $Users;

        $Permissions = $this->createMock(PermissionManager::class);
        $Permissions->method('getPermissions')->willReturn([]);
        QUI::$Rights = $Permissions;

        $Locale = $this->createMock(Locale::class);
        $Locale->method('get')->willReturn('Permission denied.');
        QUI::$Locale = $Locale;
    }
}
