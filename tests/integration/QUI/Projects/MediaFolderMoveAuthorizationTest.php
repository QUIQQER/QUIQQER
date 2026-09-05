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

final class MediaFolderMoveAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'media-folder-move-auth-';

    private Ajax $Ajax;
    private Item $ChildFile;
    private Media $Media;
    private Folder $NestedFolder;
    private Project $Project;
    private User $Root;
    private Folder $SourceFolder;
    private Folder $SourceParent;
    private Folder $TargetFolder;
    private User $User;
    private string $newFullPath;
    private string $newPath;
    private string $originalChildPath;
    private string $originalFullPath;
    private string $originalNestedPath;
    private string $originalPath;
    private string $temporaryFile;
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
        require dirname(__DIR__, 4) . '/admin/ajax/media/move.php';
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

    public function testMoveRejectsMissingSourceEditPermission(): void
    {
        $this->setMovePermissions(false, true);
        $this->setActor($this->User);

        try {
            $this->SourceFolder->moveTo($this->TargetFolder, $this->User);
            self::fail('Restricted source folder was moved.');
        } catch (QUI\Permissions\Exception) {
        }

        $this->assertMoveIsUnchanged();
    }

    public function testMoveRejectsMissingTargetEditPermission(): void
    {
        $this->setMovePermissions(true, false);
        $this->setActor($this->User);

        try {
            $this->SourceFolder->moveTo($this->TargetFolder, $this->User);
            self::fail('Folder was moved into a restricted target.');
        } catch (QUI\Permissions\Exception) {
        }

        $this->assertMoveIsUnchanged();
    }

    public function testLegacyMoveAjaxUsesSessionUserPermissions(): void
    {
        $this->setMovePermissions(false, true);
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_media_move', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'to' => $this->TargetFolder->getId(),
            'ids' => json_encode([$this->SourceFolder->getId()], JSON_THROW_ON_ERROR)
        ]);

        self::assertNull($response['result'] ?? null);
        $this->assertMoveIsUnchanged();
    }

    public function testMoveRejectsSelfAndDescendantTargets(): void
    {
        $this->setActor($this->Root);

        foreach ([$this->SourceFolder, $this->NestedFolder] as $InvalidTarget) {
            try {
                $this->SourceFolder->moveTo($InvalidTarget, $this->Root);
                self::fail('Folder was moved into itself or a descendant.');
            } catch (QUI\Exception $Exception) {
                self::assertStringContainsString('cannot be moved', $Exception->getMessage());
            }

            $this->assertMoveIsUnchanged();
        }
    }

    public function testFilesystemFailureRollsBackDatabaseChanges(): void
    {
        $this->setActor($this->Root);
        self::assertTrue(mkdir($this->newFullPath));
        file_put_contents($this->newFullPath . '/conflict.txt', 'filesystem conflict');

        try {
            $this->SourceFolder->moveTo($this->TargetFolder, $this->Root);
            self::fail('Folder move did not fail for an existing filesystem destination.');
        } catch (QUI\Exception) {
        }

        $this->assertMoveIsUnchanged(true);
        self::assertFileExists($this->newFullPath . '/conflict.txt');
    }

    public function testGetParentIdsRejectsExistingCycle(): void
    {
        $Connection = QUI::getDataBaseConnection();

        $Connection->update(
            $this->Media->getTable('relations'),
            ['parent' => $this->NestedFolder->getId()],
            ['child' => $this->SourceFolder->getId()]
        );

        try {
            $this->SourceFolder->getParentIds();
            self::fail('Existing media hierarchy cycle was not detected.');
        } catch (QUI\Exception $Exception) {
            self::assertStringContainsString('Cycle detected', $Exception->getMessage());
        } finally {
            $Connection->update(
                $this->Media->getTable('relations'),
                ['parent' => $this->SourceParent->getId()],
                ['child' => $this->SourceFolder->getId()]
            );
        }

        $this->assertMoveIsUnchanged();
    }

    public function testAuthorizedMoveUpdatesHierarchyAndFilesystem(): void
    {
        $this->setMovePermissions(true, true);
        $this->setActor($this->User);

        $this->SourceFolder->moveTo($this->TargetFolder, $this->User);

        self::assertSame($this->TargetFolder->getId(), $this->SourceFolder->getParentId());
        self::assertSame($this->TargetFolder->getId(), $this->getStoredParentId($this->SourceFolder->getId()));
        self::assertSame($this->newPath, $this->getStoredPath($this->SourceFolder->getId()));
        self::assertSame(
            str_replace($this->originalPath, $this->newPath, $this->originalNestedPath),
            $this->getStoredPath($this->NestedFolder->getId())
        );
        self::assertSame(
            str_replace($this->originalPath, $this->newPath, $this->originalChildPath),
            $this->getStoredPath($this->ChildFile->getId())
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
        file_put_contents($this->temporaryFile, 'media folder move authorization test');

        ProjectTestHelper::runAsSystemUser(function () use ($suffix): void {
            $RootFolder = $this->Media->firstChild();
            $this->SourceParent = $RootFolder->createFolder(self::TEST_PREFIX . 'parent-' . $suffix);
            $this->SourceFolder = $this->SourceParent->createFolder(self::TEST_PREFIX . 'source-' . $suffix);
            $this->NestedFolder = $this->SourceFolder->createFolder(self::TEST_PREFIX . 'nested-' . $suffix);
            $this->TargetFolder = $RootFolder->createFolder(self::TEST_PREFIX . 'target-' . $suffix);
            $this->ChildFile = $this->NestedFolder->uploadFile($this->temporaryFile);
        });

        $this->originalPath = (string)$this->SourceFolder->getAttribute('file');
        $this->originalNestedPath = (string)$this->NestedFolder->getAttribute('file');
        $this->originalChildPath = (string)$this->ChildFile->getAttribute('file');
        $this->originalFullPath = rtrim($this->SourceFolder->getFullPath(), '/');
        $this->newPath = $this->TargetFolder->getPath() . $this->SourceFolder->getAttribute('name') . '/';
        $this->newFullPath = rtrim($this->Media->getFullPath() . $this->newPath, '/');
    }

    private function setMovePermissions(bool $allowSource, bool $allowTarget): void
    {
        $this->setActor($this->Root);
        $AllowedSourceUser = $allowSource ? $this->User : $this->Root;
        $AllowedTargetUser = $allowTarget ? $this->User : $this->Root;

        QUI::getPermissionManager()->setMediaPermissions($this->SourceFolder, [
            'quiqqer.projects.media.edit' => [$AllowedSourceUser]
        ], $this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->TargetFolder, [
            'quiqqer.projects.media.edit' => [$AllowedTargetUser]
        ], $this->Root);
    }

    private function assertMoveIsUnchanged(bool $destinationMayExist = false): void
    {
        self::assertSame($this->SourceParent->getId(), $this->getStoredParentId($this->SourceFolder->getId()));
        self::assertSame($this->originalPath, $this->getStoredPath($this->SourceFolder->getId()));
        self::assertSame($this->originalNestedPath, $this->getStoredPath($this->NestedFolder->getId()));
        self::assertSame($this->originalChildPath, $this->getStoredPath($this->ChildFile->getId()));
        self::assertDirectoryExists($this->originalFullPath);

        if (!$destinationMayExist) {
            self::assertDirectoryDoesNotExist($this->newFullPath);
        }
    }

    private function getStoredParentId(int $id): int
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        return (int)$Connection->createQueryBuilder()
            ->select($Platform->quoteSingleIdentifier('parent'))
            ->from($Platform->quoteSingleIdentifier($this->Media->getTable('relations')))
            ->where($Platform->quoteSingleIdentifier('child') . ' = :child')
            ->setParameter('child', $id)
            ->executeQuery()
            ->fetchOne();
    }

    private function getStoredPath(int $id): string
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        return (string)$Connection->createQueryBuilder()
            ->select($Platform->quoteSingleIdentifier('file'))
            ->from($Platform->quoteSingleIdentifier($this->Media->getTable()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchOne();
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

            foreach ([$this->SourceParent ?? null, $this->TargetFolder ?? null] as $Folder) {
                if (!$Folder instanceof Folder) {
                    continue;
                }

                try {
                    $this->Media->get($Folder->getId())->delete();
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
