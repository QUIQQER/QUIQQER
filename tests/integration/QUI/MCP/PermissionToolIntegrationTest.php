<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Users\User as UserInterface;
use QUI\MCP\Groups\AddGroupUsers;
use QUI\MCP\Groups\CreateGroup;
use QUI\MCP\Permissions\GetEffectivePermission;
use QUI\MCP\Permissions\GetGroupPermissions;
use QUI\MCP\Permissions\GetMediaPermissions;
use QUI\MCP\Permissions\GetProjectPermissions;
use QUI\MCP\Permissions\GetSitePermissions;
use QUI\MCP\Permissions\GetUserPermissions;
use QUI\MCP\Permissions\ListPermissions;
use QUI\MCP\Permissions\UpdateGroupPermissions;
use QUI\MCP\Permissions\UpdateMediaPermissions;
use QUI\MCP\Permissions\UpdateProjectPermissions;
use QUI\MCP\Permissions\UpdateSitePermissions;
use QUI\MCP\Permissions\UpdateUserPermissions;
use QUI\MCP\Users\CreateUser;
use QUI\Permissions\Permission;
use QUI\Projects\ProjectIntegrationTestCase;
use QUI\Projects\ProjectTestHelper;
use QUI\Projects\Site\Edit;
use ReflectionProperty;
use Throwable;

class PermissionToolIntegrationTest extends ProjectIntegrationTestCase
{
    public function testPermissionManagementLifecycle(): void
    {
        self::skipIfDatabaseOrSuperUserIsUnavailable();
        $Project = self::getTestProject();
        $projectName = $Project->getName();
        $username = 'codex-mcp-permissions-user-' . uniqid();
        $groupName = 'codex-mcp-permissions-group-' . uniqid();
        $userUuid = null;
        $groupUuid = null;
        $siteIds = [];
        $mediaFolderId = null;
        $originalProjectPermission = null;

        self::runAsRootUser(function (UserInterface $RootUser) use (
            $Project,
            $projectName,
            $username,
            $groupName,
            &$userUuid,
            &$groupUuid,
            &$siteIds,
            &$mediaFolderId,
            &$originalProjectPermission
        ): void {
            try {
                $catalog = self::invokeTool(new ListPermissions(), ['global']);
                self::assertContains(
                    'quiqqer.admin.users.view',
                    array_column($catalog['permissions'], 'name')
                );

                $createdUser = self::invokeTool(new CreateUser(), [$username, []]);
                $userUuid = (string)$createdUser['user']['uuid'];
                $createdGroup = self::invokeTool(new CreateGroup(), [
                    $groupName,
                    QUI::conf('globals', 'root')
                ]);
                $groupUuid = (string)$createdGroup['group']['uuid'];

                self::invokeTool(new AddGroupUsers(), [$groupUuid, [$userUuid]]);

                self::invokeTool(new UpdateUserPermissions(), [
                    $userUuid,
                    ['quiqqer.admin.users.view' => true]
                ]);
                $userPermissions = self::invokeTool(new GetUserPermissions(), [$userUuid]);
                self::assertTrue($userPermissions['permissions']['quiqqer.admin.users.view']);

                self::invokeTool(new UpdateGroupPermissions(), [
                    $groupUuid,
                    ['quiqqer.admin.users.create' => true]
                ]);
                $groupPermissions = self::invokeTool(new GetGroupPermissions(), [$groupUuid]);
                self::assertTrue($groupPermissions['permissions']['quiqqer.admin.users.create']);

                $effectiveGlobal = self::invokeTool(new GetEffectivePermission(), [
                    $userUuid,
                    'quiqqer.admin.users.create'
                ]);
                self::assertTrue($effectiveGlobal['value'], json_encode($effectiveGlobal) ?: '');
                self::assertNull($effectiveGlobal['directUserValue']);
                self::assertContains(true, array_column($effectiveGlobal['groupValues'], 'value'));

                $aclValue = 'u' . $userUuid;
                $originalProjectPermission = QUI::getPermissionManager()
                    ->getProjectPermissions($Project)['quiqqer.project.edit'];
                self::invokeTool(new UpdateProjectPermissions(), [
                    $projectName,
                    ['quiqqer.project.edit' => $aclValue],
                    $Project->getLang()
                ]);
                $projectPermissions = self::invokeTool(new GetProjectPermissions(), [
                    $projectName,
                    $Project->getLang()
                ]);
                self::assertSame($aclValue, $projectPermissions['permissions']['quiqqer.project.edit']);

                $effectiveProject = self::invokeTool(new GetEffectivePermission(), [
                    $userUuid,
                    'quiqqer.project.edit',
                    [
                        'type' => 'project',
                        'project' => $projectName,
                        'lang' => $Project->getLang()
                    ]
                ]);
                self::assertTrue($effectiveProject['value']);

                [$parentId, $childId] = ProjectTestHelper::runAsSystemUser(
                    static function () use ($Project): array {
                        $Root = $Project->firstChild()->getEdit();
                        $parentId = $Root->createChild([
                            'name' => 'mcp-permission-parent-' . uniqid(),
                            'title' => 'MCP permission parent'
                        ]);
                        $Parent = new Edit($Project, $parentId);
                        $childId = $Parent->createChild([
                            'name' => 'mcp-permission-child-' . uniqid(),
                            'title' => 'MCP permission child'
                        ]);

                        return [$parentId, $childId];
                    }
                );
                $siteIds = [$parentId, $childId];

                $siteUpdate = self::invokeTool(new UpdateSitePermissions(), [
                    $projectName,
                    $parentId,
                    ['quiqqer.projects.site.view' => $aclValue],
                    $Project->getLang(),
                    true
                ]);
                self::assertContains($childId, $siteUpdate['updatedChildren']);
                self::assertSame([], $siteUpdate['errors']);

                $childPermissions = self::invokeTool(new GetSitePermissions(), [
                    $projectName,
                    $childId,
                    $Project->getLang()
                ]);
                self::assertSame(
                    $aclValue,
                    $childPermissions['permissions']['quiqqer.projects.site.view']
                );

                $effectiveSite = self::invokeTool(new GetEffectivePermission(), [
                    $userUuid,
                    'quiqqer.projects.site.view',
                    [
                        'type' => 'site',
                        'project' => $projectName,
                        'lang' => $Project->getLang(),
                        'id' => $childId
                    ]
                ]);
                self::assertTrue($effectiveSite['value']);

                $Folder = ProjectTestHelper::runAsSystemUser(
                    static fn() => $Project->getMedia()->firstChild()->createFolder(
                        'mcp-permission-media-' . uniqid()
                    )
                );
                $mediaFolderId = $Folder->getId();
                self::invokeTool(new UpdateMediaPermissions(), [
                    $projectName,
                    $Folder->getId(),
                    ['quiqqer.projects.media.view' => $aclValue]
                ]);
                $mediaPermissions = self::invokeTool(new GetMediaPermissions(), [
                    $projectName,
                    $Folder->getId()
                ]);
                self::assertSame(
                    $aclValue,
                    $mediaPermissions['permissions']['quiqqer.projects.media.view']
                );

                $effectiveMedia = self::invokeTool(new GetEffectivePermission(), [
                    $userUuid,
                    'quiqqer.projects.media.view',
                    [
                        'type' => 'media',
                        'project' => $projectName,
                        'id' => $Folder->getId()
                    ]
                ]);
                self::assertTrue($effectiveMedia['value']);
            } finally {
                ProjectTestHelper::runAsSystemUser(static function () use (
                    $Project,
                    $mediaFolderId,
                    $siteIds,
                    $originalProjectPermission
                ): void {
                    if ($mediaFolderId !== null) {
                        try {
                            $Item = $Project->getMedia()->get($mediaFolderId);

                            if ($Item instanceof QUI\Interfaces\Projects\Media\File) {
                                QUI::getPermissionManager()->removeMediaPermissions(
                                    $Item,
                                    QUI::getUsers()->getSystemUser()
                                );
                            }

                            $Item->delete();
                        } catch (Throwable) {
                        }
                    }

                    if ($siteIds !== []) {
                        try {
                            $Parent = new Edit($Project, $siteIds[0]);
                            $Parent->delete();

                            foreach (array_reverse($siteIds) as $siteId) {
                                $Site = new Edit($Project, $siteId);
                                QUI::getPermissionManager()->removeSitePermissions(
                                    $Site,
                                    QUI::getUsers()->getSystemUser()
                                );
                                $Site->refresh();
                                $Site->destroy();
                            }
                        } catch (Throwable) {
                        }
                    }

                    if ($originalProjectPermission !== null) {
                        try {
                            QUI::getPermissionManager()->setProjectPermissions(
                                $Project,
                                ['quiqqer.project.edit' => $originalProjectPermission],
                                QUI::getUsers()->getSystemUser()
                            );
                        } catch (Throwable) {
                        }
                    }
                });

                if ($groupUuid !== null) {
                    try {
                        QUI::getGroups()->get($groupUuid)->delete();
                    } catch (Throwable) {
                    }
                }

                if ($userUuid !== null) {
                    try {
                        QUI::getUsers()->get($userUuid)->delete($RootUser);
                    } catch (Throwable) {
                    }
                }
            }
        });
    }

    /**
     * @param array<int, mixed> $arguments
     * @return array<string, mixed>
     */
    private static function invokeTool(ToolInterface $Tool, array $arguments): array
    {
        $Builder = new Builder();
        $Tool->register($Builder);
        $ToolsProperty = new ReflectionProperty(Builder::class, 'tools');
        $tools = $ToolsProperty->getValue($Builder);
        $Handler = $tools[0]['handler'] ?? $tools[0]['callback'] ?? null;

        self::assertIsCallable($Handler);
        $result = $Handler(...$arguments);

        self::assertIsArray($result);

        return $result;
    }

    private static function skipIfDatabaseOrSuperUserIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
            $RootUser = QUI::getUsers()->get(QUI::conf('globals', 'rootuser'));
        } catch (Throwable $Exception) {
            self::markTestSkipped('QUIQQER user database is unavailable: ' . $Exception->getMessage());
        }

        if (!$RootUser->isSU()) {
            self::markTestSkipped('QUIQQER database has no usable super-user fixture.');
        }
    }

    private static function runAsRootUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $RootUser = $Users->get(QUI::conf('globals', 'rootuser'));
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $PermissionProperty = new ReflectionProperty(Permission::class, 'User');
        $RequestUserProperty = new ReflectionProperty(Server::class, 'RequestUser');
        $PreviousSessionUser = $SessionProperty->getValue($Users);
        $PreviousPermissionUser = $PermissionProperty->getValue();
        $PreviousRequestUser = $RequestUserProperty->getValue();

        $SessionProperty->setValue($Users, $RootUser);
        $PermissionProperty->setValue(null, $RootUser);
        $RequestUserProperty->setValue(null, $RootUser);

        try {
            return $Callback($RootUser);
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionProperty->setValue(null, $PreviousPermissionUser);
            $RequestUserProperty->setValue(null, $PreviousRequestUser);
        }
    }
}
