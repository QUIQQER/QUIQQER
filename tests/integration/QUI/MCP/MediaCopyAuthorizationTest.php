<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Project\Media\CopyMedia;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Projects\Media;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Item;
use QUI\Projects\Project;
use QUI\Projects\ProjectTestHelper;
use QUI\Projects\ProjectAuthorizationTestCase;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

final class MediaCopyAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'media-copy-auth-';

    private Ajax $Ajax;
    private Media $Media;
    private Project $Project;
    private Folder $SourceFolder;
    private Folder $NestedFolder;
    private Folder $TargetFolder;
    private Item $RestrictedFile;
    private User $Root;
    private User $User;
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
        $this->createMediaFixture();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/media/copy.php';
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
            $this->requestUserProperty->setValue(null, $this->previousRequestUser);
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
            parent::tearDown();
        }

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testMcpFolderCopyRejectsRestrictedDescendant(): void
    {
        $this->setRestrictedFileViewPermission($this->Root);
        $result = $this->copySourceFolderAsRestrictedUser();

        self::assertSame(0, $result['copied']);
        self::assertCount(1, $result['errors']);
        self::assertSame($this->SourceFolder->getId(), $result['errors'][0]['id']);
        self::assertFalse($this->TargetFolder->childWithNameExists($this->SourceFolder->getAttribute('name')));
    }

    public function testMcpFolderCopyAllowsReadableDescendants(): void
    {
        $this->setRestrictedFileViewPermission($this->User);
        $result = $this->copySourceFolderAsRestrictedUser();

        self::assertSame(1, $result['copied']);
        self::assertSame([], $result['errors']);

        $CopiedSource = $this->TargetFolder->getChildByName($this->SourceFolder->getAttribute('name'));
        self::assertInstanceOf(Folder::class, $CopiedSource);
        $CopiedNested = $CopiedSource->getChildByName($this->NestedFolder->getAttribute('name'));
        self::assertInstanceOf(Folder::class, $CopiedNested);
        $CopiedFile = $CopiedNested->getChildByName($this->RestrictedFile->getAttribute('name'));

        self::assertSame('restricted media copy content', file_get_contents($CopiedFile->getFullPath()));
    }

    public function testLegacyFolderCopyRejectsRestrictedSource(): void
    {
        $this->setSourceFolderViewPermission($this->Root);
        $response = $this->copySourceFolderThroughLegacyAjax();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response, JSON_PRETTY_PRINT));
        self::assertFalse($this->TargetFolder->childWithNameExists($this->SourceFolder->getAttribute('name')));
    }

    public function testLegacyFolderCopyRejectsMissingTargetUploadPermission(): void
    {
        $this->setTargetFolderPermissions($this->Root, $this->User);
        $response = $this->copySourceFolderThroughLegacyAjax();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response, JSON_PRETTY_PRINT));
        self::assertFalse($this->TargetFolder->childWithNameExists($this->SourceFolder->getAttribute('name')));
    }

    public function testLegacyFolderCopyRejectsMissingTargetEditPermission(): void
    {
        $this->setTargetFolderPermissions($this->User, $this->Root);
        $response = $this->copySourceFolderThroughLegacyAjax();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response, JSON_PRETTY_PRINT));
        self::assertFalse($this->TargetFolder->childWithNameExists($this->SourceFolder->getAttribute('name')));
    }

    public function testLegacyFolderCopyAllowsAuthorizedSourceAndTarget(): void
    {
        $this->setSourceFolderViewPermission($this->User);
        $this->setTargetFolderPermissions($this->User, $this->User);
        $response = $this->copySourceFolderThroughLegacyAjax();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response, JSON_PRETTY_PRINT));
        $CopiedSource = $this->TargetFolder->getChildByName($this->SourceFolder->getAttribute('name'));

        self::assertInstanceOf(Folder::class, $CopiedSource);
        self::assertSame($this->TargetFolder->getId(), $CopiedSource->getParentId());
    }

    /**
     * @return array<string, mixed>
     */
    private function copySourceFolderAsRestrictedUser(): array
    {
        $this->setActor($this->Root);
        $this->requestUserProperty->setValue(null, $this->User);
        $Builder = new Builder();
        (new CopyMedia())->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(
            $this->Project->getName(),
            [$this->SourceFolder->getId()],
            $this->TargetFolder->getId()
        );

        self::assertIsArray($result);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function copySourceFolderThroughLegacyAjax(): array
    {
        $this->setActor($this->User);

        return $this->Ajax->callRequestFunction('ajax_media_copy', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'to' => $this->TargetFolder->getId(),
            'ids' => json_encode([$this->SourceFolder->getId()], JSON_THROW_ON_ERROR)
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

    private function createMediaFixture(): void
    {
        $this->temporaryFile = sys_get_temp_dir() . '/' . self::TEST_PREFIX . bin2hex(random_bytes(5)) . '.txt';
        file_put_contents($this->temporaryFile, 'restricted media copy content');

        ProjectTestHelper::runAsSystemUser(function (): void {
            $RootFolder = $this->Media->firstChild();
            $this->SourceFolder = $RootFolder->createFolder(
                self::TEST_PREFIX . 'source-' . bin2hex(random_bytes(4))
            );
            $this->NestedFolder = $this->SourceFolder->createFolder(
                self::TEST_PREFIX . 'nested-' . bin2hex(random_bytes(4))
            );
            $this->TargetFolder = $RootFolder->createFolder(
                self::TEST_PREFIX . 'target-' . bin2hex(random_bytes(4))
            );
            $this->RestrictedFile = $this->NestedFolder->uploadFile($this->temporaryFile);
        });
    }

    private function setRestrictedFileViewPermission(User $AllowedUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->RestrictedFile, [
            'quiqqer.projects.media.view' => [$AllowedUser]
        ], $this->Root);
    }

    private function setSourceFolderViewPermission(User $AllowedUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->SourceFolder, [
            'quiqqer.projects.media.view' => [$AllowedUser]
        ], $this->Root);
    }

    private function setTargetFolderPermissions(User $UploadUser, User $EditUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->TargetFolder, [
            'quiqqer.projects.media.upload' => [$UploadUser],
            'quiqqer.projects.media.edit' => [$EditUser]
        ], $this->Root);
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

            foreach ([$this->SourceFolder ?? null, $this->TargetFolder ?? null] as $Folder) {
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
        $this->requestUserProperty->setValue(null, $User);
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
