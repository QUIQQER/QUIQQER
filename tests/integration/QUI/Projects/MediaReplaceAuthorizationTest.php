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
use QUI\QDOM;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

final class MediaReplaceAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'media-replace-auth-';
    private const ORIGINAL_CONTENT = 'original restricted media content';
    private const REPLACEMENT_CONTENT = 'replacement media content';

    private Ajax $Ajax;
    private Media $Media;
    private Project $Project;
    private User $Root;
    private Folder $TargetFolder;
    private Item $TargetFile;
    private User $User;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousMediaPermissions;
    private mixed $previousPermissionUser;
    private mixed $previousSession;
    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $mediaPermissionsProperty;
    private ReflectionProperty $permissionUserProperty;
    private string $replacementFile;
    private string $sourceFile;

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
        require dirname(__DIR__, 4) . '/admin/ajax/media/replace.php';
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

    public function testReplaceRejectsExplicitUserWithoutEditPermission(): void
    {
        $this->setTargetEditPermission($this->Root);
        $this->setActor($this->User);

        try {
            $this->Media->replace(
                $this->TargetFile->getId(),
                $this->replacementFile,
                $this->User
            );
            self::fail('Restricted media content was replaced.');
        } catch (QUI\Permissions\Exception) {
        }

        $this->assertTargetIsUnchanged();
    }

    public function testLegacyAjaxReplaceUsesSessionUserForEditPermission(): void
    {
        $this->setTargetEditPermission($this->Root);
        $response = $this->callAjaxReplace();

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(403, $response['Exception']['code'] ?? null);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type'] ?? null);
        $this->assertTargetIsUnchanged();
    }

    public function testReplaceAllowsUserWithEditPermission(): void
    {
        $this->setTargetEditPermission($this->User);
        $this->setActor($this->User);
        $Replaced = $this->Media->replace(
            $this->TargetFile->getId(),
            $this->replacementFile,
            $this->User
        );

        self::assertSame($this->TargetFile->getId(), $Replaced->getId());
        self::assertSame(self::REPLACEMENT_CONTENT, file_get_contents($Replaced->getFullPath()));
    }

    /**
     * @return array<string, mixed>
     */
    private function callAjaxReplace(): array
    {
        $File = new QDOM();
        $File->setAttribute('filepath', $this->replacementFile);
        $this->setActor($this->User);

        return $this->Ajax->callRequestFunction('ajax_media_replace', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->Project->getName(),
            'fileid' => $this->TargetFile->getId(),
            'File' => $File
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
        $this->sourceFile = sys_get_temp_dir() . '/' . self::TEST_PREFIX . $suffix . '-source.txt';
        $this->replacementFile = sys_get_temp_dir() . '/' . self::TEST_PREFIX . $suffix . '-replacement.txt';
        file_put_contents($this->sourceFile, self::ORIGINAL_CONTENT);
        file_put_contents($this->replacementFile, self::REPLACEMENT_CONTENT);

        ProjectTestHelper::runAsSystemUser(function (): void {
            $this->TargetFolder = $this->Media->firstChild()->createFolder(
                self::TEST_PREFIX . 'target-' . bin2hex(random_bytes(4))
            );
            $TargetFile = $this->TargetFolder->uploadFile($this->sourceFile);

            self::assertInstanceOf(Item::class, $TargetFile);
            $this->TargetFile = $TargetFile;
        });
    }

    private function setTargetEditPermission(User $AllowedUser): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setMediaPermissions($this->TargetFile, [
            'quiqqer.projects.media.edit' => [$AllowedUser]
        ], $this->Root);
    }

    private function assertTargetIsUnchanged(): void
    {
        $TargetFile = $this->Media->get($this->TargetFile->getId());

        self::assertSame(self::ORIGINAL_CONTENT, file_get_contents($TargetFile->getFullPath()));
        self::assertFileExists($this->replacementFile);
        self::assertSame(self::REPLACEMENT_CONTENT, file_get_contents($this->replacementFile));
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

        foreach ([$this->sourceFile ?? null, $this->replacementFile ?? null] as $temporaryFile) {
            if (is_string($temporaryFile) && file_exists($temporaryFile)) {
                unlink($temporaryFile);
            }
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
