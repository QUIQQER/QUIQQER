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
final class SiteChildrenEndpointAuthorizationTest extends TestCase
{
    private const TEST_PREFIX = 'site-children-auth-';
    private const CHILD_TITLE = 'Restricted child';

    private Ajax $Ajax;
    private Project $Project;
    private User $AllowedUser;
    private User $DeniedUser;
    private User $Root;
    private int $parentId;
    private int $childId;
    private string $childName;
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
        $this->createRestrictedHierarchy();

        $this->Ajax = new Ajax();
        QUI::$Ajax = $this->Ajax;
        require dirname(__DIR__, 4) . '/admin/ajax/site/getchildren.php';
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

    public function testEndpointRejectsBackendUserOutsideParentViewAcl(): void
    {
        $this->setActor($this->DeniedUser);

        $response = $this->requestChildren();

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type']);
        self::assertStringNotContainsString($this->childName, json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function testEndpointDoesNotDiscloseRestrictedChildAttributes(): void
    {
        $this->allowBothUsersToViewParent();
        $this->setActor($this->DeniedUser);

        $response = $this->requestChildren('name,title');

        self::assertArrayHasKey('Exception', $response);
        self::assertSame(QUI\Permissions\Exception::class, $response['Exception']['type']);
        self::assertStringNotContainsString($this->childName, json_encode($response, JSON_THROW_ON_ERROR));
        self::assertStringNotContainsString(self::CHILD_TITLE, json_encode($response, JSON_THROW_ON_ERROR));
    }

    public function testEndpointReturnsFullAndSelectedAttributesToAuthorizedUser(): void
    {
        $this->setActor($this->AllowedUser);

        $fullResponse = $this->requestChildren();
        $selectedResponse = $this->requestChildren('name');

        self::assertArrayNotHasKey('Exception', $fullResponse, json_encode($fullResponse) ?: '');
        self::assertSame(
            $this->childName,
            $fullResponse['result']['children'][0]['name'] ?? null,
            json_encode($fullResponse, JSON_THROW_ON_ERROR)
        );
        self::assertArrayNotHasKey('Exception', $selectedResponse, json_encode($selectedResponse) ?: '');
        self::assertSame(
            $this->childName,
            $selectedResponse['result']['children'][0]['name'] ?? null,
            json_encode($selectedResponse, JSON_THROW_ON_ERROR)
        );
        self::assertArrayNotHasKey('title', $selectedResponse['result']['children'][0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestChildren(?string $attributes = null): array
    {
        $params = [];

        if ($attributes !== null) {
            $params['attributes'] = $attributes;
        }

        return $this->Ajax->callRequestFunction('ajax_site_getchildren', [
            'project' => json_encode([
                'name' => $this->Project->getName(),
                'lang' => $this->Project->getLang()
            ], JSON_THROW_ON_ERROR),
            'id' => $this->parentId,
            'params' => json_encode($params, JSON_THROW_ON_ERROR),
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

    private function createRestrictedHierarchy(): void
    {
        $this->childName = self::TEST_PREFIX . bin2hex(random_bytes(5));

        ProjectTestHelper::runAsSystemUser(function (): void {
            $RootSite = $this->Project->firstChild()->getEdit();
            $this->parentId = $RootSite->createChild([
                'name' => self::TEST_PREFIX . bin2hex(random_bytes(5)),
                'title' => 'Restricted parent'
            ]);
            $Parent = new Edit($this->Project, $this->parentId);
            $this->childId = $Parent->createChild([
                'name' => $this->childName,
                'title' => self::CHILD_TITLE
            ]);

            $PermissionManager = QUI::getPermissionManager();
            $System = QUI::getUsers()->getSystemUser();
            $PermissionManager->setPermissions($Parent, [
                'quiqqer.projects.site.view' => 'u' . $this->AllowedUser->getUUID()
            ], $System);
            $PermissionManager->setPermissions(new Edit($this->Project, $this->childId), [
                'quiqqer.projects.site.view' => 'u' . $this->AllowedUser->getUUID()
            ], $System);
        });
    }

    private function allowBothUsersToViewParent(): void
    {
        $this->setActor($this->Root);
        $Parent = new Edit($this->Project, $this->parentId);
        QUI::getPermissionManager()->setPermissions($Parent, [
            'quiqqer.projects.site.view' => implode(',', [
                'u' . $this->AllowedUser->getUUID(),
                'u' . $this->DeniedUser->getUUID()
            ])
        ], QUI::getUsers()->getSystemUser());
    }

    private function cleanupSites(): void
    {
        if (!isset($this->Project)) {
            return;
        }

        ProjectTestHelper::runAsSystemUser(function (): void {
            foreach ([$this->childId ?? null, $this->parentId ?? null] as $siteId) {
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
