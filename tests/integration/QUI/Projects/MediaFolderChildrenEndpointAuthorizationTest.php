<?php

declare(strict_types=1);

namespace QUI\Projects;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use QUI;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Projects\Media\Folder;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class MediaFolderChildrenEndpointAuthorizationTest extends TestCase
{
    private const TEST_PREFIX = 'media-folder-children-auth-';
    private const CHILD_TITLE = 'Restricted media child';

    private Ajax $Ajax;
    private Folder $ChildFolder;
    private Media $Media;
    private Project $Project;
    private Folder $RestrictedFolder;
    private User $Root;
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
        require dirname(__DIR__, 4) . '/admin/ajax/media/folder/children.php';
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
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
        }

        parent::tearDown();

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testEndpointRejectsBackendUserOutsideFolderViewAcl(): void
    {
        $this->setFolderViewPermission($this->Root);
        $response = $this->requestChildren();

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(403, $response['Exception']['code'] ?? null);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type'] ?? null);
        self::assertStringNotContainsString(
            (string)$this->ChildFolder->getAttribute('name'),
            json_encode($response, JSON_THROW_ON_ERROR)
        );
        self::assertStringNotContainsString(self::CHILD_TITLE, json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function testEndpointReturnsChildrenToBackendUserInsideFolderViewAcl(): void
    {
        $this->setFolderViewPermission($this->User);
        $response = $this->requestChildren();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response, JSON_PRETTY_PRINT));
        self::assertSame(1, $response['result']['total'] ?? null);
        self::assertSame(
            $this->ChildFolder->getAttribute('name'),
            $response['result']['data'][0]['name'] ?? null,
            json_encode($response, JSON_PRETTY_PRINT)
        );
        self::assertStringContainsString(
            self::CHILD_TITLE,
            (string)($response['result']['data'][0]['title'] ?? '')
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestChildren(): array
    {
        $this->setActor($this->User);

        return $this->Ajax->callRequestFunction('ajax_media_folder_children', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'folderid' => $this->RestrictedFolder->getId(),
            'params' => json_encode([], JSON_THROW_ON_ERROR)
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
            'quiqqer.admin' => true
        ], $this->Root);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function createMediaFixture(): void
    {
        $suffix = bin2hex(random_bytes(5));

        ProjectTestHelper::runAsSystemUser(function () use ($suffix): void {
            $this->RestrictedFolder = $this->Media->firstChild()->createFolder(
                self::TEST_PREFIX . 'parent-' . $suffix
            );
            $this->ChildFolder = $this->RestrictedFolder->createFolder(
                self::TEST_PREFIX . 'child-' . $suffix
            );
            $this->ChildFolder->setTitle(self::CHILD_TITLE);
            $this->ChildFolder->save();
        });
    }

    private function setFolderViewPermission(User $AllowedUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->RestrictedFolder, [
            'quiqqer.projects.media.view' => [$AllowedUser]
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

            if (isset($this->RestrictedFolder)) {
                try {
                    $Folder = $this->Media->get($this->RestrictedFolder->getId());
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
