<?php

namespace QUI\Upload;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Interfaces\Users\User;
use QUI\Locale;
use QUI\Permissions\Exception as PermissionException;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Users\Manager as UserManager;

use function bin2hex;
use function file_get_contents;
use function random_bytes;
use function strpos;
use function substr;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class LegacyUploadEndpointTest extends TestCase
{
    public function testDeniedPermissionStopsBeforeUploadManagerRuns(): void
    {
        $uuid = 'denied-upload-' . bin2hex(random_bytes(8));
        $uploadDirectory = VAR_DIR . 'uploads/' . $uuid;
        self::assertDirectoryDoesNotExist($uploadDirectory);

        $User = $this->createMock(User::class);
        $User->method('getUUID')->willReturn($uuid);
        $User->method('isSU')->willReturn(false);
        $User->method('getGroups')->willReturn([]);

        $Users = $this->createMock(UserManager::class);
        $Users->method('getUserBySession')->willReturn($User);
        $Users->method('isSystemUser')->willReturn(false);
        QUI::$Users = $Users;

        $Permissions = $this->createMock(PermissionManager::class);
        $Permissions->method('getPermissions')->willReturn([]);
        QUI::$Rights = $Permissions;

        $Locale = $this->createMock(Locale::class);
        $Locale->method('get')->willReturn('Permission denied.');
        QUI::$Locale = $Locale;

        $_GET = [];
        $_REQUEST = [
            'filename' => '../outside.txt'
        ];

        try {
            require dirname(__DIR__, 4) . '/src/QUI/Upload/bin/upload.php';
            self::fail('Legacy upload continued after denied permission.');
        } catch (PermissionException) {
            self::assertDirectoryDoesNotExist($uploadDirectory);
        }
    }

    public function testPermissionBoundaryPrecedesAuthorizedUploadFlow(): void
    {
        $endpoint = (string)file_get_contents(
            dirname(__DIR__, 4) . '/src/QUI/Upload/bin/upload.php'
        );
        $permissionPosition = strpos(
            $endpoint,
            "Permission::checkPermission('quiqqer.frontend.upload')"
        );
        $managerPosition = strpos($endpoint, 'new QUI\\Upload\\Manager()');
        $initPosition = strpos($endpoint, '$QUM->init()');

        self::assertIsInt($permissionPosition);
        self::assertIsInt($managerPosition);
        self::assertIsInt($initPosition);
        self::assertLessThan($managerPosition, $permissionPosition);
        self::assertLessThan($initPosition, $managerPosition);
        self::assertStringNotContainsString(
            'catch',
            substr($endpoint, $permissionPosition, $managerPosition - $permissionPosition)
        );
    }
}
