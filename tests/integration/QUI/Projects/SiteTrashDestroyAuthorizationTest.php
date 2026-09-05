<?php

declare(strict_types=1);

namespace QUI\Projects;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Ajax;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Project\Trash\DestroySites;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Permissions\Permission;
use QUI\Projects\Site\Edit;
use QUI\Security\CsrfToken;
use QUI\System\Console\Session as ConsoleSession;
use QUI\Users\Manager as UserManager;
use QUI\Users\User;
use ReflectionProperty;
use Throwable;

final class SiteTrashDestroyAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'site-trash-destroy-auth-';

    private Ajax $Ajax;
    private Project $Project;
    private User $User;
    private User $Root;
    private int $siteId;
    private mixed $previousAjax;
    private mixed $previousManagerSession;
    private mixed $previousPermissionUser;
    private mixed $previousRequestUser;
    private mixed $previousSession;
    private ReflectionProperty $managerSessionProperty;
    private ReflectionProperty $permissionUserProperty;
    private ReflectionProperty $requestUserProperty;

    protected function setUp(): void
    {
        parent::setUp();

        $this->skipIfDatabaseIsUnavailable();

        $this->managerSessionProperty = new ReflectionProperty(QUI::getUsers(), 'Session');
        $this->permissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $this->requestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $this->previousAjax = QUI::$Ajax;
        $this->previousManagerSession = $this->managerSessionProperty->getValue(QUI::getUsers());
        $this->previousPermissionUser = $this->permissionUserProperty->getValue();
        $this->previousRequestUser = $this->requestUserProperty->getValue();
        $this->previousSession = QUI::$Session;

        $this->Root = $this->getRootUser();
        $this->setActor($this->Root);
        $this->cleanupUsers();

        $this->Project = ProjectTestHelper::getProject();
        $this->User = $this->createBackendUser(false);
        $this->siteId = $this->createDeletedSite();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/trash/destroy.php';
        require dirname(__DIR__, 4) . '/admin/ajax/trash/clear.php';
    }

    protected function tearDown(): void
    {
        if (!isset($this->managerSessionProperty)) {
            parent::tearDown();
            return;
        }

        $cleanupFailure = null;

        try {
            $this->cleanupSite();
            $this->cleanupUsers();
        } catch (Throwable $Exception) {
            $cleanupFailure = $Exception;
        } finally {
            $this->managerSessionProperty->setValue(QUI::getUsers(), $this->previousManagerSession);
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

    public function testTrashDestroyEndpointRejectsBackendUserWithoutSiteDeletePermission(): void
    {
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_trash_destroy', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->encodeProject(),
            'ids' => json_encode([$this->siteId], JSON_THROW_ON_ERROR)
        ]);

        self::assertArrayHasKey('Exception', $response);
        self::assertTrue($this->siteExists());
    }

    public function testTrashClearEndpointRejectsBackendUserWithoutSiteDeletePermission(): void
    {
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_trash_clear', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->encodeProject()
        ]);

        self::assertArrayHasKey('Exception', $response);
        self::assertTrue($this->siteExists());
    }

    public function testMcpDestroyRejectsBackendUserWithoutSiteDeletePermission(): void
    {
        $this->setActor($this->User);
        $Builder = new Builder();
        (new DestroySites())->register($Builder);
        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);

        $result = $Handler(
            $this->Project->getName(),
            [$this->siteId],
            true,
            $this->Project->getLang()
        );

        self::assertInstanceOf(CallToolResult::class, $result);
        self::assertTrue($this->siteExists());
    }

    public function testTrashDestroyEndpointAllowsBackendUserWithSiteDeletePermission(): void
    {
        $this->setUserPermissions(true);
        $this->setActor($this->User);

        $response = $this->Ajax->callRequestFunction('ajax_trash_destroy', [
            '_csrf' => CsrfToken::get(),
            'project' => $this->encodeProject(),
            'ids' => json_encode([$this->siteId], JSON_THROW_ON_ERROR)
        ]);

        self::assertArrayNotHasKey('Exception', $response, json_encode($response) ?: '');
        self::assertFalse($this->siteExists());
    }

    private function createBackendUser(bool $canDeleteSites): User
    {
        $username = self::TEST_PREFIX . bin2hex(random_bytes(5));
        $System = QUI::getUsers()->getSystemUser();
        $User = QUI::getUsers()->createChildWithAttributes([
            'username' => $username,
            'email' => $username . '@example.invalid'
        ], $System);

        self::assertInstanceOf(User::class, $User);

        $this->User = $User;
        $this->setUserPermissions($canDeleteSites);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function setUserPermissions(bool $canDeleteSites): void
    {
        $this->setActor($this->Root);
        QUI::getPermissionManager()->setPermissions($this->User, [
            'quiqqer.admin' => true,
            'quiqqer.core.mcp.canUse' => true,
            'quiqqer.projects.sites.view' => true,
            'quiqqer.projects.sites.edit' => true,
            'quiqqer.projects.sites.del' => $canDeleteSites
        ], $this->Root);
    }

    private function createDeletedSite(): int
    {
        return ProjectTestHelper::runAsSystemUser(function (): int {
            $RootSite = $this->Project->firstChild()->getEdit();
            $siteId = $RootSite->createChild([
                'name' => self::TEST_PREFIX . bin2hex(random_bytes(5)),
                'title' => 'Site trash authorization test'
            ]);
            (new Edit($this->Project, $siteId))->delete();

            return $siteId;
        });
    }

    private function cleanupSite(): void
    {
        if (!isset($this->Project, $this->siteId) || !$this->siteExists()) {
            return;
        }

        ProjectTestHelper::runAsSystemUser(function (): void {
            $Site = new Edit($this->Project, $this->siteId);

            if ((int)$Site->getAttribute('deleted') !== 1) {
                $Site->delete();
            }

            $Site->destroy();
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
        $this->requestUserProperty->setValue(null, $User);
    }

    private function encodeProject(): string
    {
        return json_encode([
            'name' => $this->Project->getName(),
            'lang' => $this->Project->getLang()
        ], JSON_THROW_ON_ERROR);
    }

    private function siteExists(): bool
    {
        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $count = $Connection->createQueryBuilder()
            ->select('COUNT(' . $Platform->quoteSingleIdentifier('id') . ')')
            ->from($Platform->quoteSingleIdentifier($this->Project->table()))
            ->where($Platform->quoteSingleIdentifier('id') . ' = :siteId')
            ->setParameter('siteId', $this->siteId)
            ->executeQuery()
            ->fetchOne();

        return (int)$count === 1;
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
