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
use QUI\Projects\Site\Edit;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class SiteLinkAuthorizationTest extends TestCase
{
    private const TEST_PREFIX = 'site-link-auth-';

    private Ajax $Ajax;
    private Project $Project;
    private User $Root;
    private User $User;
    private int $sourceId;
    private int $targetId;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousPermissionUser;
    private mixed $previousSession;
    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $permissionUserProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();
        $this->previousSession = QUI::$Session;

        $this->Root = $this->getRootUser();
        $this->setActor($this->Root);
        $this->cleanupUsers();

        $this->Project = ProjectTestHelper::getProject();
        $this->User = $this->createBackendUser();
        $this->createSiteFixture();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/site/linked.php';
    }

    protected function tearDown(): void
    {
        $cleanupFailure = null;

        try {
            $this->cleanupSites();
            $this->cleanupUsers();
        } catch (Throwable $Exception) {
            $cleanupFailure = $Exception;
        } finally {
            $this->managerSessionProperty->setValue(QUI::getUsers(), $this->previousManagerSession);
            $this->permissionUserProperty->setValue(null, $this->previousPermissionUser);
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
        }

        parent::tearDown();

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testLinkRejectsMissingSourceEditPermission(): void
    {
        $this->setLinkPermissions(false, true);
        $this->setActor($this->User);

        try {
            (new Edit($this->Project, $this->sourceId))->linked($this->targetId);
            self::fail('A site without source edit permission was linked.');
        } catch (QUI\Permissions\Exception) {
        }

        self::assertFalse($this->linkExists());
    }

    public function testLinkRejectsMissingTargetNewPermission(): void
    {
        $this->setLinkPermissions(true, false);
        $this->setActor($this->User);

        try {
            (new Edit($this->Project, $this->sourceId))->linked($this->targetId);
            self::fail('A site was linked below a target without new-site permission.');
        } catch (QUI\Permissions\Exception) {
        }

        self::assertFalse($this->linkExists());
    }

    public function testLegacyAjaxRejectsUnauthorizedLink(): void
    {
        $this->setLinkPermissions(false, true);
        $this->setActor($this->User);

        $response = $this->requestLink();

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type']);
        self::assertFalse($this->linkExists());
    }

    public function testLegacyAjaxAllowsAuthorizedLink(): void
    {
        $this->setLinkPermissions(true, true);
        $this->setActor($this->User);

        $response = $this->requestLink();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response) ?: '');
        self::assertTrue($this->linkExists());
    }

    public function testLinkRejectsUnknownTarget(): void
    {
        $this->setLinkPermissions(true, true);
        $this->setActor($this->User);
        $unknownTargetId = max($this->sourceId, $this->targetId) + 1_000_000;

        try {
            (new Edit($this->Project, $this->sourceId))->linked($unknownTargetId);
            self::fail('A site was linked below an unknown target.');
        } catch (QUI\Exception $Exception) {
            self::assertSame(705, $Exception->getCode());
        }

        self::assertFalse($this->linkExists());
    }

    /**
     * @return array<string, mixed>
     */
    private function requestLink(): array
    {
        return $this->Ajax->callRequestFunction('ajax_site_linked', [
            '_csrf' => CsrfToken::get(),
            'project' => json_encode([
                'name' => $this->Project->getName(),
                'lang' => $this->Project->getLang()
            ], JSON_THROW_ON_ERROR),
            'id' => $this->sourceId,
            'newParentId' => $this->targetId
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
            'quiqqer.projects.sites.view' => true,
            'quiqqer.projects.sites.edit' => true,
            'quiqqer.projects.sites.new' => true
        ], $this->Root);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function createSiteFixture(): void
    {
        ProjectTestHelper::runAsSystemUser(function (): void {
            $RootSite = $this->Project->firstChild()->getEdit();
            $this->sourceId = $RootSite->createChild([
                'name' => self::TEST_PREFIX . 'source-' . bin2hex(random_bytes(5)),
                'title' => 'Site link authorization source'
            ]);
            $this->targetId = $RootSite->createChild([
                'name' => self::TEST_PREFIX . 'target-' . bin2hex(random_bytes(5)),
                'title' => 'Site link authorization target'
            ]);
        });
    }

    private function setLinkPermissions(bool $allowSource, bool $allowTarget): void
    {
        $this->setActor($this->Root);
        $SourceUser = $allowSource ? $this->User : $this->Root;
        $TargetUser = $allowTarget ? $this->User : $this->Root;
        $PermissionManager = QUI::getPermissionManager();

        $PermissionManager->setSitePermissions(
            new Edit($this->Project, $this->sourceId),
            ['quiqqer.projects.site.edit' => [$SourceUser]],
            $this->Root
        );
        $PermissionManager->setSitePermissions(
            new Edit($this->Project, $this->targetId),
            ['quiqqer.projects.site.new' => [$TargetUser]],
            $this->Root
        );
    }

    private function linkExists(): bool
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();

        $count = $Connection->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($Platform->quoteSingleIdentifier($this->Project->table() . '_relations'))
            ->where($Platform->quoteSingleIdentifier('parent') . ' = :parent')
            ->andWhere($Platform->quoteSingleIdentifier('child') . ' = :child')
            ->setParameter('parent', $this->targetId)
            ->setParameter('child', $this->sourceId)
            ->executeQuery()
            ->fetchOne();

        return (int)$count === 1;
    }

    private function cleanupSites(): void
    {
        if (!isset($this->Project)) {
            return;
        }

        ProjectTestHelper::runAsSystemUser(function (): void {
            $Connection = QUI::getDataBaseConnection();
            $Connection->delete($this->Project->table() . '_relations', [
                'parent' => $this->targetId,
                'child' => $this->sourceId
            ]);

            foreach ([$this->sourceId ?? null, $this->targetId ?? null] as $siteId) {
                if ($siteId === null) {
                    continue;
                }

                try {
                    $Site = new Edit($this->Project, $siteId);
                    $Site->delete();
                    $Site->destroy();
                } catch (QUI\Exception $Exception) {
                    if ($Exception->getCode() !== 705) {
                        throw $Exception;
                    }
                }
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
