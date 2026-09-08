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
use function file_get_contents;
use function file_put_contents;
use function random_bytes;
use function rtrim;
use function strlen;
use function unlink;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class ManagerSaveDeleteToolbarSecurityTest extends TestCase
{
    public function testDeniedPermissionStopsBeforeToolbarSave(): void
    {
        $toolbar = 'denied-save-' . bin2hex(random_bytes(8));
        $file = Manager::getToolbarsPath() . $toolbar . '.xml';
        $originalXml = '<toolbar><item name="original"/></toolbar>';

        $this->createFixture($file, $originalXml);
        $this->setActor(false);

        try {
            Manager::saveToolbar($toolbar, '<toolbar><item name="changed"/></toolbar>');
            self::fail('Toolbar save continued after denied permission.');
        } catch (PermissionException) {
            self::assertSame($originalXml, file_get_contents($file));
        } finally {
            $this->removeFixture($file);
        }
    }

    public function testAuthorizedSaveTraversalIsRejected(): void
    {
        $toolbar = 'outside-save-' . bin2hex(random_bytes(8));
        $toolbarDirectory = rtrim(Manager::getToolbarsPath(), '/\\');
        $outsideFile = dirname($toolbarDirectory) . '/' . $toolbar . '.xml';
        $originalXml = '<configuration><value>original</value></configuration>';

        $this->createFixture($outsideFile, $originalXml);
        $this->setActor(true);

        try {
            Manager::saveToolbar('../' . $toolbar, '<configuration><value>changed</value></configuration>');
            self::fail('Toolbar save accepted a path outside the toolbar directory.');
        } catch (QUI\Exception $Exception) {
            self::assertSame(400, $Exception->getCode());
            self::assertSame($originalXml, file_get_contents($outsideFile));
        } finally {
            $this->removeFixture($outsideFile);
        }
    }

    public function testDeniedPermissionStopsBeforeToolbarDeletion(): void
    {
        $toolbar = 'denied-delete-' . bin2hex(random_bytes(8));
        $file = Manager::getToolbarsPath() . $toolbar . '.xml';

        $this->createFixture($file, '<toolbar/>');
        $this->setActor(false);

        try {
            Manager::deleteToolbar($toolbar . '.xml');
            self::fail('Toolbar deletion continued after denied permission.');
        } catch (PermissionException) {
            self::assertFileExists($file);
        } finally {
            $this->removeFixture($file);
        }
    }

    private function createFixture(string $file, string $contents): void
    {
        self::assertFileDoesNotExist($file);
        self::assertSame(strlen($contents), file_put_contents($file, $contents));
    }

    private function removeFixture(string $file): void
    {
        if (file_exists($file)) {
            unlink($file);
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
