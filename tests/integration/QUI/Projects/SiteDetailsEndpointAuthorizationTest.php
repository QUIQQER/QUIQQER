<?php

declare(strict_types=1);

namespace QUI\Projects;

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

final class SiteDetailsEndpointAuthorizationTest extends ProjectAuthorizationTestCase
{
    private const TEST_PREFIX = 'site-details-auth-';
    private const SITE_TITLE = 'Restricted site details';

    private Ajax $Ajax;
    private Project $Project;
    private User $AllowedUser;
    private User $DeniedUser;
    private User $Root;
    private int $siteId;
    private string $siteName;
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
        $this->AllowedUser = $this->createBackendUser();
        $this->DeniedUser = $this->createBackendUser();
        $this->siteId = $this->createRestrictedSite();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/site/get.php';
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
            QUI::$Session = $this->previousSession;
            QUI::$Ajax = $this->previousAjax;
            parent::tearDown();
        }

        if ($cleanupFailure !== null) {
            throw $cleanupFailure;
        }
    }

    public function testEndpointRejectsBackendUserOutsideSiteViewAcl(): void
    {
        $this->setActor($this->DeniedUser);

        $response = $this->requestSiteDetails();

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type']);
        self::assertStringNotContainsString($this->siteName, json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function testEndpointReturnsDetailsToBackendUserInsideSiteViewAcl(): void
    {
        $this->setActor($this->AllowedUser);

        $response = $this->requestSiteDetails();

        self::assertArrayNotHasKey('Exception', $response, json_encode($response) ?: '');
        self::assertSame(
            $this->siteName,
            $response['result']['attributes']['name'] ?? null,
            json_encode($response, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function requestSiteDetails(): array
    {
        return $this->Ajax->callRequestFunction('ajax_site_get', [
            'project' => json_encode([
                'name' => $this->Project->getName(),
                'lang' => $this->Project->getLang()
            ], JSON_THROW_ON_ERROR),
            'id' => $this->siteId,
            '_csrf' => CsrfToken::get()
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
            'quiqqer.projects.sites.view' => true
        ], $this->Root);
        $User->setPassword(self::TEST_PREFIX . bin2hex(random_bytes(8)), $System);
        $User->activate('', $System);

        return $User;
    }

    private function createRestrictedSite(): int
    {
        $this->siteName = self::TEST_PREFIX . bin2hex(random_bytes(5));

        return ProjectTestHelper::runAsSystemUser(function (): int {
            $RootSite = $this->Project->firstChild()->getEdit();
            $siteId = $RootSite->createChild([
                'name' => $this->siteName,
                'title' => self::SITE_TITLE
            ]);
            $Site = new Edit($this->Project, $siteId);

            QUI::getPermissionManager()->setPermissions($Site, [
                'quiqqer.projects.site.view' => 'u' . $this->AllowedUser->getUUID()
            ], QUI::getUsers()->getSystemUser());

            return $siteId;
        });
    }

    private function cleanupSite(): void
    {
        if (!isset($this->Project, $this->siteId)) {
            return;
        }

        ProjectTestHelper::runAsSystemUser(function (): void {
            try {
                $Site = new Edit($this->Project, $this->siteId);
                $Site->delete();
                $Site->destroy();
            } catch (QUI\Exception $Exception) {
                if ($Exception->getCode() !== 705) {
                    throw $Exception;
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
