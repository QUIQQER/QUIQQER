<?php

declare(strict_types=1);

namespace QUI\MCP;

use Mcp\Server\Builder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
use QUI\MCP\Project\GetSetting;
use QUI\MCP\Project\ListSettings;
use QUI\MCP\Project\SetSetting;
use QUI\MCP\Project\UpdateSettings;
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
        yield 'add project language' => [
            new AddLanguage(),
            'quiqqer_projects_add_language',
            ['project', 'lang']
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
