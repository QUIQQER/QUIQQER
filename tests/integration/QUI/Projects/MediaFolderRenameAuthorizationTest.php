<?php

declare(strict_types=1);

namespace QUI\Projects;

use QUI;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Item;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

final class MediaFolderRenameAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'media-folder-rename-auth-';

    private Ajax $Ajax;
    private Item $ChildFile;
    private Media $Media;
    private string $newName;
    private string $newPath;
    private string $newFullPath;
    private string $originalChildPath;
    private string $originalFullPath;
    private string $originalName;
    private string $originalPath;
    private Project $Project;
    private User $Root;
    private Folder $TargetFolder;
    private string $temporaryFile;
    private User $User;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousMediaPermissions;
    private mixed $previousPermissionUser;
    private mixed $previousSession;
    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $mediaPermissionsProperty;
    private ReflectionProperty $permissionUserProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->mediaPermissionsProperty = new ReflectionProperty(Media::class, 'mediaPermissions');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousMediaPermissions = $this->mediaPermissionsProperty->getValue();
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();
        $this->previousSession = QUI::$Session;
        $this->mediaPermissionsProperty->setValue(null, true);

        $this->Root = $this->getRootUser();
        $this->setActor($this->Root);
        $this->cleanupUsers();

        $this->Project = ProjectTestHelper::getProject();
        $this->Media = $this->Project->getMedia();
        $this->User = $this->createBackendUser();
        $this->createMediaFixture();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/media/rename.php';
        require dirname(__DIR__, 4) . '/admin/ajax/media/file/save.php';
    }

    protected function tearDown(): void
    {
        if (!isset($this->managerSessionProperty)) {
            parent::tearDown();
            return;
        }

        $cleanupFailure = null;

        try {
            $this->cleanupMediaFixtures();
            $this->cleanupUsers();
        } catch (Throwable $Exception) {
            $cleanupFailure = $Exception;
        } finally {
            $this->managerSessionProperty->setValue(QUI::getUsers(), $this->previousManagerSession);
            $this->mediaPermissionsProperty->setValue(null, $this->previousMediaPermissions);
            $this->permissionUserProperty->setValue(null, $this->previousPermissionUser);
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
            parent::tearDown();
        }

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testRenameRejectsExplicitUserWithoutEditPermission(): void
    {
        $this->setTargetEditPermission($this->Root);
        $this->setActor($this->User);

        try {
            $this->TargetFolder->rename($this->newName, $this->User);
            self::fail('Restricted media folder was renamed.');
        } catch (QUI\Permissions\Exception) {
        }

        $this->assertTargetIsUnchanged();
    }

    public function testLegacyRenameAjaxUsesSessionUserForEditPermission(): void
    {
        $this->setTargetEditPermission($this->Root);
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_media_rename', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'id' => $this->TargetFolder->getId(),
            'newname' => $this->newName
        ]);

        $this->assertPermissionDenied($response);
        $this->assertTargetIsUnchanged();
    }

    public function testLegacyFileSaveAjaxChecksEditPermissionBeforeRename(): void
    {
        $this->setTargetEditPermission($this->Root);
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_media_file_save', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'fileid' => $this->TargetFolder->getId(),
            'attributes' => json_encode(['name' => $this->newName], JSON_THROW_ON_ERROR)
        ]);

        $this->assertPermissionDenied($response);
        $this->assertTargetIsUnchanged();
    }

    public function testRenameAllowsUserWithEditPermission(): void
    {
        $this->setTargetEditPermission($this->User);
        $this->setActor($this->User);

        $this->TargetFolder->rename($this->newName, $this->User);

        $folderRow = $this->getMediaRow($this->TargetFolder->getId());
        $childRow = $this->getMediaRow($this->ChildFile->getId());

        self::assertSame($this->newName, $folderRow['name']);
        self::assertSame($this->newPath, $folderRow['file']);
        self::assertSame(
            $this->newPath . basename($this->originalChildPath),
            $childRow['file']
        );
        self::assertDirectoryDoesNotExist($this->originalFullPath);
        self::assertDirectoryExists($this->newFullPath);
    }

    private function createBackendUser(): User
    {
        $username = self::TEST_PREFIX . bin2hex(random_bytes(5));
        $System = QUI::getUsers()->getSystemUser();
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $System);

        self::assertInstanceOf(User::class, $User);

        QUI::getPermissionManager()->setPermissions($User, [
            'quiqqer.admin' => true
        ], $this->Root);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function createMediaFixture(): void
    {
        $suffix = bin2hex(random_bytes(5));
        $this->temporaryFile = sys_get_temp_dir() . '/' . self::TEST_PREFIX . $suffix . '.txt';
        file_put_contents($this->temporaryFile, 'media folder rename authorization test');

        ProjectTestHelper::runAsSystemUser(function () use ($suffix): void {
            $this->TargetFolder = $this->Media->firstChild()->createFolder(
                self::TEST_PREFIX . 'target-' . $suffix
            );
            $this->ChildFile = $this->TargetFolder->uploadFile($this->temporaryFile);
        });

        $this->originalName = (string)$this->TargetFolder->getAttribute('name');
        $this->originalPath = (string)$this->TargetFolder->getAttribute('file');
        $this->originalFullPath = rtrim($this->TargetFolder->getFullPath(), '/');
        $this->originalChildPath = (string)$this->ChildFile->getAttribute('file');
        $this->newName = self::TEST_PREFIX . 'renamed-' . $suffix;
        $this->newPath = $this->TargetFolder->getParent()->getPath() . $this->newName . '/';
        $this->newFullPath = dirname($this->originalFullPath) . '/' . $this->newName;
    }

    private function setTargetEditPermission(User $AllowedUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->TargetFolder, [
            'quiqqer.projects.media.edit' => [$AllowedUser]
        ], $this->Root);
    }

    /**
     * @param array<string, mixed> $response
     */
    private function assertPermissionDenied(array $response): void
    {
        self::assertArrayHasKey('Exception', $response);
        self::assertSame(403, $response['Exception']['code'] ?? null);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type'] ?? null);
    }

    private function assertTargetIsUnchanged(): void
    {
        $folderRow = $this->getMediaRow($this->TargetFolder->getId());
        $childRow = $this->getMediaRow($this->ChildFile->getId());

        self::assertSame($this->originalName, $folderRow['name']);
        self::assertSame($this->originalPath, $folderRow['file']);
        self::assertSame($this->originalChildPath, $childRow['file']);
        self::assertDirectoryExists($this->originalFullPath);
        self::assertDirectoryDoesNotExist($this->newFullPath);
    }

    /**
     * @return array{name: string, file: string}
     */
    private function getMediaRow(int $id): array
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $row = $Connection->createQueryBuilder()
            ->select('name', 'file')
            ->from($Platform->quoteSingleIdentifier($this->Media->getTable()))
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        self::assertIsArray($row);

        return [
            'name' => (string)$row['name'],
            'file' => (string)$row['file']
        ];
    }

    private function cleanupMediaFixtures(): void
    {
        if (!isset($this->Media)) {
            return;
        }

        ProjectTestHelper::runAsSystemUser(function (): void {
            $Connection = QUI::getDataBaseConnection();
            $Platform = $Connection->getDatabasePlatform();
            $rows = $Connection->createQueryBuilder()
                ->select('id')
                ->from($Platform->quoteSingleIdentifier($this->Media->getTable()))
                ->where($Platform->quoteSingleIdentifier('name') . ' LIKE :prefix')
                ->setParameter('prefix', self::TEST_PREFIX . '%')
                ->executeQuery()
                ->fetchAllAssociative();

            if (isset($this->TargetFolder)) {
                try {
                    $this->Media->get($this->TargetFolder->getId())->delete();
                } catch (QUI\Exception) {
                }
            }

            foreach ($rows as $row) {
                $Connection->delete(PermissionManager::table() . '2media', [
                    'project' => $this->Project->getName(),
                    'id' => (int)$row['id']
                ]);
            }
        });

        if (isset($this->temporaryFile) && file_exists($this->temporaryFile)) {
            unlink($this->temporaryFile);
        }
    }

    private function cleanupUsers(): void
    {
        $this->setActor(QUI::getUsers()->getSystemUser());
        $Connection = QUI::getDataBaseConnection();
        $permissionTable = PermissionManager::table() . '2users';
        $users = $Connection->createQueryBuilder()
            ->select('uuid')
            ->from(QUI\Utils\Doctrine::quoteIdentifier(UserManager::table()))
            ->where('username LIKE :prefix')
            ->setParameter('prefix', self::TEST_PREFIX . '%')
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($users as $user) {
            $uuid = (string)$user['uuid'];
            $Connection->delete($permissionTable, ['user_id' => $uuid]);

            try {
                QUI::getUsers()->get($uuid)->delete(QUI::getUsers()->getSystemUser());
            } catch (QUI\Users\Exception $Exception) {
                if ($Exception->getCode() !== 404) {
                    throw $Exception;
                }
            }
        }
    }

    private function setActor(UserInterface $User): void
    {
        $Session = new ConsoleSession();
        $Session->set('uid', (string)$User->getUUID());
        $Session->set('username', $User->getUsername());
        $Session->set('auth', 1);
        $Session->set('auth-primary', 1);
        $Session->set('auth-secondary', 1);

        QUI::$Session = $Session;
        $this->managerSessionProperty->setValue(QUI::getUsers(), $User);
        $this->permissionUserProperty->setValue(null, null);
    }

    private function getRootUser(): User
    {
        $Root = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));

        self::assertInstanceOf(User::class, $Root);
        self::assertTrue($Root->isSU(), 'The local fixture root user must be an SU.');

        return $Root;
    }

    private function skipIfDatabaseIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $this->getRootUser();
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER database fixtures are unavailable: ' . $Exception->getMessage());
        }
    }
}
