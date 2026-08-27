<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use QUI\MCP\Forwarding\CreateForwarding;
use QUI\MCP\Forwarding\DeleteForwardings;
use QUI\MCP\Forwarding\GetForwarding;
use QUI\MCP\Forwarding\ListForwardings;
use QUI\MCP\Forwarding\UpdateForwarding;
use QUI\MCP\Groups\ActivateGroup;
use QUI\MCP\Groups\AddGroupUsers;
use QUI\MCP\Groups\CreateGroup;
use QUI\MCP\Groups\DeactivateGroup;
use QUI\MCP\Groups\DeleteGroup;
use QUI\MCP\Groups\GetGroup;
use QUI\MCP\Groups\ListGroups;
use QUI\MCP\Groups\ListGroupUsers;
use QUI\MCP\Groups\ListUserGroups;
use QUI\MCP\Groups\RemoveGroupUsers;
use QUI\MCP\Groups\SearchGroups;
use QUI\MCP\Groups\UpdateGroup;
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
use QUI\MCP\Project\AddLanguage;
use QUI\MCP\Project\CreateDefaultStructure;
use QUI\MCP\Project\CreateProject;
use QUI\MCP\Project\DeleteProject;
use QUI\MCP\Project\GetProject;
use QUI\MCP\Project\ListAvailableLanguages;
use QUI\MCP\Project\ListDemoDataSets;
use QUI\MCP\Project\ListProjectTemplates;
use QUI\MCP\Project\Media\CopyMedia;
use QUI\MCP\Project\Media\CreateImageVariant;
use QUI\MCP\Project\Media\DownloadMedia;
use QUI\MCP\Project\Media\DownloadMediaFolder;
use QUI\MCP\Project\Media\GetMediaEffects;
use QUI\MCP\Project\Media\GetMediaFolderPreview;
use QUI\MCP\Project\Media\GetMediaFolderSize;
use QUI\MCP\Project\Media\MoveMedia;
use QUI\MCP\Project\Media\RenameMedia;
use QUI\MCP\Project\Media\ReplaceMedia;
use QUI\MCP\Project\Media\UpdateMediaEffects;
use QUI\MCP\Project\Media\UpdateMediaFolderPreview;
use QUI\MCP\Project\Media\UpdateMediaOrder;
use QUI\MCP\Project\Media\UpdateMediaVisibility;
use QUI\MCP\Project\GetSetting;
use QUI\MCP\Project\ListSettings;
use QUI\MCP\Project\RenameProject;
use QUI\MCP\Project\SetSetting;
use QUI\MCP\Project\Sites\ClearSiteCache;
use QUI\MCP\Project\Sites\CreateSiteCache;
use QUI\MCP\Project\Sites\GetSiteLock;
use QUI\MCP\Project\Sites\LinkSite;
use QUI\MCP\Project\Sites\ListSiteLayouts;
use QUI\MCP\Project\Sites\ListSiteTypes;
use QUI\MCP\Project\Sites\LockSite;
use QUI\MCP\Project\Sites\RemoveLanguageLink;
use QUI\MCP\Project\Sites\UnlinkSite;
use QUI\MCP\Project\Sites\UnlockSite;
use QUI\MCP\Project\UpdateSettings;
use QUI\MCP\Project\Trash\ClearMediaTrash;
use QUI\MCP\Project\Trash\ClearSiteTrash;
use QUI\MCP\Project\Trash\DestroyMedia;
use QUI\MCP\Project\Trash\DestroySites;
use QUI\MCP\Project\Trash\ListMediaTrash;
use QUI\MCP\Project\Trash\ListSiteTrash;
use QUI\MCP\Project\Trash\RestoreMedia;
use QUI\MCP\Project\Trash\RestoreSites;
use QUI\MCP\System\GetSystemInfo;
use QUI\MCP\Users\ActivateUser;
use QUI\MCP\Users\CreateUser;
use QUI\MCP\Users\DeactivateUser;
use QUI\MCP\Users\DeleteUser;
use QUI\MCP\Users\GetUser;
use QUI\MCP\Users\ListUsers;
use QUI\MCP\Users\SearchUsers;
use QUI\MCP\Users\UpdateUser;
use QUI\MCP\VHost\CreateVHost;
use QUI\MCP\VHost\DeleteVHost;
use QUI\MCP\VHost\GetVHost;
use QUI\MCP\VHost\ListVHosts;
use QUI\MCP\VHost\UpdateVHost;
use ReflectionProperty;

class ToolRegistrationTest extends TestCase
{
    /**
     * @return iterable<string, array{ToolInterface, string, array<int, string>}>
     */
    public static function toolProvider(): iterable
    {
        yield 'list forwardings' => [
            new ListForwardings(),
            'quiqqer_forwardings_list',
            []
        ];
        yield 'get forwarding' => [
            new GetForwarding(),
            'quiqqer_forwardings_get',
            ['source']
        ];
        yield 'create forwarding' => [
            new CreateForwarding(),
            'quiqqer_forwardings_create',
            ['source', 'target']
        ];
        yield 'update forwarding' => [
            new UpdateForwarding(),
            'quiqqer_forwardings_update',
            ['source', 'target']
        ];
        yield 'delete forwardings' => [
            new DeleteForwardings(),
            'quiqqer_forwardings_delete',
            ['sources']
        ];
        yield 'get project' => [
            new GetProject(),
            'quiqqer_projects_get',
            ['project']
        ];
        yield 'create project' => [
            new CreateProject(),
            'quiqqer_projects_create',
            ['name', 'defaultLanguage']
        ];
        yield 'rename project' => [
            new RenameProject(),
            'quiqqer_projects_rename',
            ['project', 'newName']
        ];
        yield 'delete project' => [
            new DeleteProject(),
            'quiqqer_projects_delete',
            ['project', 'confirm']
        ];
        yield 'create default project structure' => [
            new CreateDefaultStructure(),
            'quiqqer_projects_create_default_structure',
            ['project']
        ];
        yield 'list available project languages' => [
            new ListAvailableLanguages(),
            'quiqqer_projects_languages_list',
            []
        ];
        yield 'list project templates' => [
            new ListProjectTemplates(),
            'quiqqer_projects_templates_list',
            []
        ];
        yield 'list project demo data sets' => [
            new ListDemoDataSets(),
            'quiqqer_projects_demo_data_list',
            ['template']
        ];
        yield 'add project language' => [
            new AddLanguage(),
            'quiqqer_projects_add_language',
            ['project', 'lang']
        ];
        yield 'remove site language link' => [
            new RemoveLanguageLink(),
            'quiqqer_sites_remove_language_link',
            ['project', 'id', 'targetLang']
        ];
        yield 'link site' => [
            new LinkSite(),
            'quiqqer_sites_link',
            ['project', 'id', 'parentId']
        ];
        yield 'unlink site' => [
            new UnlinkSite(),
            'quiqqer_sites_unlink',
            ['project', 'id', 'parentId']
        ];
        yield 'get site lock' => [
            new GetSiteLock(),
            'quiqqer_sites_lock_get',
            ['project', 'id']
        ];
        yield 'lock site' => [
            new LockSite(),
            'quiqqer_sites_lock',
            ['project', 'id']
        ];
        yield 'unlock site' => [
            new UnlockSite(),
            'quiqqer_sites_unlock',
            ['project', 'id']
        ];
        yield 'list site types' => [
            new ListSiteTypes(),
            'quiqqer_sites_types_list',
            []
        ];
        yield 'list site layouts' => [
            new ListSiteLayouts(),
            'quiqqer_sites_layouts_list',
            ['project']
        ];
        yield 'clear site cache' => [
            new ClearSiteCache(),
            'quiqqer_sites_cache_clear',
            ['project', 'id']
        ];
        yield 'create site cache' => [
            new CreateSiteCache(),
            'quiqqer_sites_cache_create',
            ['project', 'id']
        ];
        yield 'list project settings' => [
            new ListSettings(),
            'quiqqer_project_settings_list',
            ['project']
        ];
        yield 'get project setting' => [
            new GetSetting(),
            'quiqqer_project_setting_get',
            ['project', 'key']
        ];
        yield 'set project setting' => [
            new SetSetting(),
            'quiqqer_project_setting_set',
            ['project', 'key', 'value']
        ];
        yield 'update project settings' => [
            new UpdateSettings(),
            'quiqqer_project_settings_update',
            ['project', 'settings']
        ];
        yield 'get system information' => [
            new GetSystemInfo(),
            'quiqqer_system_info_get',
            []
        ];
        yield 'list VHosts' => [
            new ListVHosts(),
            'quiqqer_vhosts_list',
            []
        ];
        yield 'get VHost' => [
            new GetVHost(),
            'quiqqer_vhosts_get',
            ['host']
        ];
        yield 'create VHost' => [
            new CreateVHost(),
            'quiqqer_vhosts_create',
            ['host', 'project', 'rootLanguage']
        ];
        yield 'update VHost' => [
            new UpdateVHost(),
            'quiqqer_vhosts_update',
            ['host']
        ];
        yield 'delete VHost' => [
            new DeleteVHost(),
            'quiqqer_vhosts_delete',
            ['host']
        ];
        yield 'list users' => [
            new ListUsers(),
            'quiqqer_users_list',
            []
        ];
        yield 'search users' => [
            new SearchUsers(),
            'quiqqer_users_search',
            ['query']
        ];
        yield 'get user' => [
            new GetUser(),
            'quiqqer_users_get',
            ['user']
        ];
        yield 'create user' => [
            new CreateUser(),
            'quiqqer_users_create',
            ['username']
        ];
        yield 'update user' => [
            new UpdateUser(),
            'quiqqer_users_update',
            ['user', 'attributes']
        ];
        yield 'activate user' => [
            new ActivateUser(),
            'quiqqer_users_activate',
            ['user']
        ];
        yield 'deactivate user' => [
            new DeactivateUser(),
            'quiqqer_users_deactivate',
            ['user']
        ];
        yield 'delete user' => [
            new DeleteUser(),
            'quiqqer_users_delete',
            ['user']
        ];
        yield 'list groups' => [
            new ListGroups(),
            'quiqqer_groups_list',
            []
        ];
        yield 'search groups' => [
            new SearchGroups(),
            'quiqqer_groups_search',
            ['query']
        ];
        yield 'get group' => [
            new GetGroup(),
            'quiqqer_groups_get',
            ['group']
        ];
        yield 'create group' => [
            new CreateGroup(),
            'quiqqer_groups_create',
            ['name', 'parent']
        ];
        yield 'update group' => [
            new UpdateGroup(),
            'quiqqer_groups_update',
            ['group', 'attributes']
        ];
        yield 'activate group' => [
            new ActivateGroup(),
            'quiqqer_groups_activate',
            ['group']
        ];
        yield 'deactivate group' => [
            new DeactivateGroup(),
            'quiqqer_groups_deactivate',
            ['group']
        ];
        yield 'delete group' => [
            new DeleteGroup(),
            'quiqqer_groups_delete',
            ['group']
        ];
        yield 'list user groups' => [
            new ListUserGroups(),
            'quiqqer_users_groups_list',
            ['user']
        ];
        yield 'list group users' => [
            new ListGroupUsers(),
            'quiqqer_groups_users_list',
            ['group']
        ];
        yield 'add group users' => [
            new AddGroupUsers(),
            'quiqqer_groups_users_add',
            ['group', 'users']
        ];
        yield 'remove group users' => [
            new RemoveGroupUsers(),
            'quiqqer_groups_users_remove',
            ['group', 'users']
        ];
        yield 'list permissions' => [
            new ListPermissions(),
            'quiqqer_permissions_list',
            []
        ];
        yield 'get user permissions' => [
            new GetUserPermissions(),
            'quiqqer_permissions_user_get',
            ['user']
        ];
        yield 'update user permissions' => [
            new UpdateUserPermissions(),
            'quiqqer_permissions_user_update',
            ['user', 'permissions']
        ];
        yield 'get group permissions' => [
            new GetGroupPermissions(),
            'quiqqer_permissions_group_get',
            ['group']
        ];
        yield 'update group permissions' => [
            new UpdateGroupPermissions(),
            'quiqqer_permissions_group_update',
            ['group', 'permissions']
        ];
        yield 'get project permissions' => [
            new GetProjectPermissions(),
            'quiqqer_permissions_project_get',
            ['project']
        ];
        yield 'update project permissions' => [
            new UpdateProjectPermissions(),
            'quiqqer_permissions_project_update',
            ['project', 'permissions']
        ];
        yield 'get site permissions' => [
            new GetSitePermissions(),
            'quiqqer_permissions_site_get',
            ['project', 'id']
        ];
        yield 'update site permissions' => [
            new UpdateSitePermissions(),
            'quiqqer_permissions_site_update',
            ['project', 'id', 'permissions']
        ];
        yield 'get media permissions' => [
            new GetMediaPermissions(),
            'quiqqer_permissions_media_get',
            ['project', 'id']
        ];
        yield 'update media permissions' => [
            new UpdateMediaPermissions(),
            'quiqqer_permissions_media_update',
            ['project', 'id', 'permissions']
        ];
        yield 'get effective permission' => [
            new GetEffectivePermission(),
            'quiqqer_permissions_effective_get',
            ['user', 'permission']
        ];
        yield 'list site trash' => [
            new ListSiteTrash(),
            'quiqqer_sites_trash_list',
            ['project']
        ];
        yield 'restore sites' => [
            new RestoreSites(),
            'quiqqer_sites_restore',
            ['project', 'ids', 'parentId']
        ];
        yield 'destroy sites' => [
            new DestroySites(),
            'quiqqer_sites_destroy',
            ['project', 'ids', 'confirm']
        ];
        yield 'clear site trash' => [
            new ClearSiteTrash(),
            'quiqqer_sites_trash_clear',
            ['project', 'confirm']
        ];
        yield 'list media trash' => [
            new ListMediaTrash(),
            'quiqqer_media_trash_list',
            ['project']
        ];
        yield 'restore media' => [
            new RestoreMedia(),
            'quiqqer_media_restore',
            ['project', 'ids', 'parentId']
        ];
        yield 'destroy media' => [
            new DestroyMedia(),
            'quiqqer_media_destroy',
            ['project', 'ids', 'confirm']
        ];
        yield 'clear media trash' => [
            new ClearMediaTrash(),
            'quiqqer_media_trash_clear',
            ['project', 'confirm']
        ];
        yield 'move media' => [
            new MoveMedia(),
            'quiqqer_media_move',
            ['project', 'ids', 'targetFolderId']
        ];
        yield 'copy media' => [
            new CopyMedia(),
            'quiqqer_media_copy',
            ['project', 'ids', 'targetFolderId']
        ];
        yield 'rename media' => [
            new RenameMedia(),
            'quiqqer_media_rename',
            ['project', 'id', 'name']
        ];
        yield 'replace media' => [
            new ReplaceMedia(),
            'quiqqer_media_replace',
            ['project', 'id', 'filename', 'contentBase64']
        ];
        yield 'update media visibility' => [
            new UpdateMediaVisibility(),
            'quiqqer_media_visibility_update',
            ['project', 'ids', 'visible']
        ];
        yield 'get media effects' => [
            new GetMediaEffects(),
            'quiqqer_media_effects_get',
            ['project', 'id']
        ];
        yield 'update media effects' => [
            new UpdateMediaEffects(),
            'quiqqer_media_effects_update',
            ['project', 'id', 'effects']
        ];
        yield 'create image variant' => [
            new CreateImageVariant(),
            'quiqqer_media_image_variant_create',
            ['project', 'id']
        ];
        yield 'update media order' => [
            new UpdateMediaOrder(),
            'quiqqer_media_order_update',
            ['project', 'folderId', 'orderedIds']
        ];
        yield 'get media folder preview' => [
            new GetMediaFolderPreview(),
            'quiqqer_media_folder_preview_get',
            ['project', 'folderId']
        ];
        yield 'update media folder preview' => [
            new UpdateMediaFolderPreview(),
            'quiqqer_media_folder_preview_update',
            ['project', 'folderId', 'imageId']
        ];
        yield 'download media' => [
            new DownloadMedia(),
            'quiqqer_media_download',
            ['project', 'id']
        ];
        yield 'download media folder' => [
            new DownloadMediaFolder(),
            'quiqqer_media_folder_download',
            ['project', 'id']
        ];
        yield 'get media folder size' => [
            new GetMediaFolderSize(),
            'quiqqer_media_folder_size_get',
            ['project', 'id']
        ];
    }

    /**
     * @param array<int, string> $required
     */
    #[DataProvider('toolProvider')]
    public function testToolRegistration(
        ToolInterface $Tool,
        string $expectedName,
        array $required
    ): void {
        $Builder = new Builder();
        $Tool->register($Builder);

        $tools = (new ReflectionProperty(Builder::class, 'tools'))->getValue($Builder);

        self::assertCount(1, $tools);
        self::assertSame($expectedName, $tools[0]['name']);
        self::assertSame($required, $tools[0]['inputSchema']['required'] ?? []);
        self::assertFalse($tools[0]['inputSchema']['additionalProperties'] ?? false);
    }

    public function testProviderContainsRegisteredTools(): void
    {
        $Provider = new Provider();
        $tools = (new ReflectionProperty(Provider::class, 'tools'))->getValue($Provider);
        $classes = array_map(
            static fn(ToolInterface $Tool): string => $Tool::class,
            $tools
        );

        foreach (self::toolProvider() as [$Tool]) {
            self::assertContains($Tool::class, $classes);
        }
    }
}
