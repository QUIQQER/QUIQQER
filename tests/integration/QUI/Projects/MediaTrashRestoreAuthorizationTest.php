<?php

declare(strict_types=1);

namespace QUI\Projects;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Project\Trash\RestoreMedia;
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

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MediaTrashRestoreAuthorizationTest extends TestCase
{
    private const TEST_PREFIX = 'media-trash-restore-auth-';

    private Ajax $Ajax;
    private Folder $SourceFolder;
    private Folder $TargetFolder;
    private Media $Media;
    private Project $Project;
    private User $Root;
    private User $User;
    private int $sourceId;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousMediaPermissions;
    private mixed $previousPermissionUser;
    private mixed $previousRequestUser;
    private mixed $previousSession;
    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $mediaPermissionsProperty;
    private ReflectionProperty $permissionUserProperty;
    private ReflectionProperty $requestUserProperty;
    private string $temporaryFile;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->mediaPermissionsProperty = new ReflectionProperty(Media::class, 'mediaPermissions');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->requestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousMediaPermissions = $this->mediaPermissionsProperty->getValue();
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();
        $this->previousRequestUser = $this->requestUserProperty->getValue();
        $this->previousSession = QUI::$Session;
        $this->mediaPermissionsProperty->setValue(null, true);

        $this->Root = $this->getRootUser();
        $this->setActor($this->Root);
        $this->cleanupUsers();

        $this->Project = ProjectTestHelper::getProject();
        $this->Media = $this->Project->getMedia();
        $this->User = $this->createBackendUser();
        $this->createDeletedMediaFixture();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/trash/media/restore.php';
    }

    protected function tearDown(): void
    {
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
            $this->requestUserProperty->setValue(null, $this->previousRequestUser);
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
        }

        parent::tearDown();

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testAjaxRestoreRejectsMissingSourceViewPermission(): void
    {
        $this->setMediaPermissions(false, true);
        $response = $this->callAjaxRestore();

        self::assertArrayHasKey('Exception', $response);
        $this->assertSourceRemainsInTrash();
    }

    public function testAjaxRestoreRejectsMissingTargetUploadPermission(): void
    {
        $this->setMediaPermissions(true, false);
        $response = $this->callAjaxRestore();

        self::assertArrayHasKey('Exception', $response);
        $this->assertSourceRemainsInTrash();
    }

    public function testRestoreAllowsAuthorizedSourceAndTarget(): void
    {
        $this->setMediaPermissions(true, true);
        $this->setActor($this->User);
        $Restored = $this->Media->getTrash()->restore(
            $this->sourceId,
            $this->TargetFolder,
            $this->User
        );

        self::assertFalse($this->mediaRecordExists($this->sourceId));
        self::assertSame($this->TargetFolder->getId(), $Restored->getParentId());
        self::assertSame('media trash restore authorization test', file_get_contents($Restored->getFullPath()));
    }

    public function testMcpRestoreUsesRequestUserForMediaPermissions(): void
    {
        $this->setMediaPermissions(false, true);
        $this->setActor($this->Root);
        $this->requestUserProperty->setValue(null, $this->User);
        $Builder = new Builder();
        (new RestoreMedia())->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);

        $result = $Handler(
            $this->Project->getName(),
            [$this->sourceId],
            $this->TargetFolder->getId()
        );

        self::assertInstanceOf(CallToolResult::class, $result);
        $this->assertSourceRemainsInTrash();
    }

    private function callAjaxRestore(): array
    {
        $this->setActor($this->User);

        return $this->Ajax->callRequestFunction('ajax_trash_media_restore', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->encodeProject(),
            'ids' => json_encode([$this->sourceId], JSON_THROW_ON_ERROR),
            'parentid' => $this->TargetFolder->getId()
        ]);
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
            'quiqqer.admin' => true,
            'quiqqer.core.mcp.canUse' => true
        ], $this->Root);
        $this->grantGlobalMediaUploadPermission($User);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function grantGlobalMediaUploadPermission(User $User): void
    {
        $Connection = QUI::getDataBaseConnection();
        $table = PermissionManager::table() . '2users';
        $permissions = $Connection->createQueryBuilder()
            ->select('permissions')
            ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
            ->where('user_id = :userId')
            ->setParameter('userId', $User->getUUID())
            ->executeQuery()
            ->fetchOne();
        $permissions = is_string($permissions) ? json_decode($permissions, true) : [];

        if (!is_array($permissions)) {
            $permissions = [];
        }

        $permissions['quiqqer.projects.media.upload'] = true;
        $Connection->update($table, [
            'permissions' => json_encode($permissions, JSON_THROW_ON_ERROR)
        ], [
            'user_id' => $User->getUUID()
        ]);
    }

    private function createDeletedMediaFixture(): void
    {
        $this->temporaryFile = sys_get_temp_dir() . '/' . self::TEST_PREFIX . bin2hex(random_bytes(5)) . '.txt';
        file_put_contents($this->temporaryFile, 'media trash restore authorization test');

        ProjectTestHelper::runAsSystemUser(function (): void {
            $RootFolder = $this->Media->firstChild();
            $this->SourceFolder = $RootFolder->createFolder(self::TEST_PREFIX . 'source-' . bin2hex(random_bytes(4)));
            $this->TargetFolder = $RootFolder->createFolder(self::TEST_PREFIX . 'target-' . bin2hex(random_bytes(4)));
            $Source = $this->SourceFolder->uploadFile($this->temporaryFile);
            $this->sourceId = $Source->getId();
            $Source->delete();
        });
    }

    private function setMediaPermissions(bool $allowSourceView, bool $allowTargetUpload): void
    {
        $this->setActor($this->Root);
        $AllowedSourceUser = $allowSourceView ? $this->User : $this->Root;
        $AllowedTargetUser = $allowTargetUpload ? $this->User : $this->Root;
        $Source = $this->Media->get($this->sourceId);

        self::assertInstanceOf(Item::class, $Source);
        QUI::getPermissionManager()->setMediaPermissions($Source, [
            'quiqqer.projects.media.view' => [$AllowedSourceUser]
        ], $this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->TargetFolder, [
            'quiqqer.projects.media.upload' => [$AllowedTargetUser],
            'quiqqer.projects.media.edit' => [$this->User]
        ], $this->Root);
    }

    private function assertSourceRemainsInTrash(): void
    {
        self::assertTrue($this->mediaRecordExists($this->sourceId));
        self::assertTrue($this->Media->get($this->sourceId)->isDeleted());
        self::assertFileExists($this->Media->getTrash()->getPath() . $this->sourceId);
    }

    private function mediaRecordExists(int $id): bool
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $count = $Connection->createQueryBuilder()
            ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
            ->from($Platform->quoteSingleIdentifier($this->Media->getTable()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchOne();

        return (int)$count === 1;
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
                ->select('id', 'type')
                ->from($Platform->quoteSingleIdentifier($this->Media->getTable()))
                ->where($Platform->quoteSingleIdentifier('name') . ' LIKE :prefix')
                ->setParameter('prefix', self::TEST_PREFIX . '%')
                ->executeQuery()
                ->fetchAllAssociative();

            foreach ($rows as $row) {
                if ($row['type'] === 'folder') {
                    continue;
                }

                try {
                    $Item = $this->Media->get((int)$row['id']);

                    if (!$Item->isDeleted()) {
                        $Item->delete();
                    }

                    $Item->destroy();
                } catch (QUI\Exception) {
                }
            }

            foreach ($rows as $row) {
                if ($row['type'] !== 'folder') {
                    continue;
                }

                try {
                    $Folder = $this->Media->get((int)$row['id']);
                    $Folder->delete();
                    $Folder->destroy();
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

        foreach (glob($this->Media->getTrash()->getPath() . self::TEST_PREFIX . '*') ?: [] as $trashFile) {
            unlink($trashFile);
        }

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
        $this->requestUserProperty->setValue(null, $User);
    }

    private function encodeProject(): string
    {
        return json_encode([
            'name' => $this->Project->getName(),
            'lang' => $this->Project->getLang()
        ], JSON_THROW_ON_ERROR);
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
