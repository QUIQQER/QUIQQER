<?php

/**
 * This file contains the \QUI\MCP\System\ClearCache
 */

namespace QUI\MCP\System;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use Throwable;

class ClearCache extends AbstractTool
{
    protected const CACHE_CLEAR_PERMISSION = 'quiqqer.core.mcp.cache.clear';

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                bool | null $compile = null,
                bool | null $templates = null,
                bool | null $complete = null,
                bool | null $settings = null,
                bool | null $quiqqer = null,
                bool | null $projects = null,
                bool | null $groups = null,
                bool | null $users = null,
                bool | null $permissions = null,
                bool | null $media = null,
                bool | null $usersGroups = null,
                bool | null $packages = null,
                bool | null $longterm = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    Permission::checkPermission(
                        self::CACHE_CLEAR_PERMISSION,
                        Server::getRequestUser()
                    );

                    $cleared = [];

                    if ($compile) {
                        QUI\Utils\System\File::unlink(VAR_DIR . 'cache/compile');
                        $cleared[] = 'compile';
                    }

                    if ($templates) {
                        QUI\Cache\Manager::clearTemplateCache();
                        $cleared[] = 'templates';
                    }

                    if ($complete) {
                        QUI\Cache\Manager::clearAll();
                        $cleared[] = 'complete';
                    }

                    if ($settings) {
                        QUI\Cache\Manager::clearSettingsCache();
                        $cleared[] = 'settings';
                    }

                    if ($quiqqer) {
                        QUI\Cache\Manager::clearCompleteQuiqqerCache();
                        $cleared[] = 'quiqqer';
                    }

                    if ($projects) {
                        QUI\Cache\Manager::clearProjectsCache();
                        $cleared[] = 'projects';
                    }

                    if ($groups) {
                        QUI\Cache\Manager::clearGroupsCache();
                        $cleared[] = 'groups';
                    }

                    if ($users) {
                        QUI\Cache\Manager::clearUsersCache();
                        $cleared[] = 'users';
                    }

                    if ($permissions) {
                        QUI\Cache\Manager::clearPermissionsCache();
                        $cleared[] = 'permissions';
                    }

                    if ($media) {
                        QUI\Cache\Manager::clearMediaCache();
                        $cleared[] = 'media';
                    }

                    if ($usersGroups) {
                        QUI\Cache\Manager::clearGroupsCache();
                        QUI\Cache\Manager::clearUsersCache();
                        QUI\Cache\Manager::clearPermissionsCache();
                        $cleared[] = 'usersGroups';
                    }

                    if ($packages) {
                        QUI\Cache\Manager::clearPackagesCache();
                        $cleared[] = 'packages';
                    }

                    if ($longterm) {
                        QUI\Cache\LongTermCache::clear();
                        $cleared[] = 'longterm';
                    }

                    return [
                        'cleared' => $cleared,
                        'success' => true
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_cache_clear',
            description: 'Clears selected QUIQQER cache areas. Requires core MCP permission and a dedicated MCP cache-clear permission.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'compile' => [
                        'type' => 'boolean',
                        'description' => 'Clear the compiled template cache.',
                        'default' => false
                    ],
                    'templates' => [
                        'type' => 'boolean',
                        'description' => 'Clear the template cache and related compiled templates.',
                        'default' => false
                    ],
                    'complete' => [
                        'type' => 'boolean',
                        'description' => 'Clear all configured cache areas.',
                        'default' => false
                    ],
                    'settings' => [
                        'type' => 'boolean',
                        'description' => 'Clear the QUIQQER settings cache.',
                        'default' => false
                    ],
                    'quiqqer' => [
                        'type' => 'boolean',
                        'description' => 'Clear the internal QUIQQER core cache.',
                        'default' => false
                    ],
                    'projects' => [
                        'type' => 'boolean',
                        'description' => 'Clear the projects cache.',
                        'default' => false
                    ],
                    'groups' => [
                        'type' => 'boolean',
                        'description' => 'Clear the groups cache.',
                        'default' => false
                    ],
                    'users' => [
                        'type' => 'boolean',
                        'description' => 'Clear the users cache.',
                        'default' => false
                    ],
                    'permissions' => [
                        'type' => 'boolean',
                        'description' => 'Clear the permissions cache.',
                        'default' => false
                    ],
                    'media' => [
                        'type' => 'boolean',
                        'description' => 'Clear the media cache.',
                        'default' => false
                    ],
                    'usersGroups' => [
                        'type' => 'boolean',
                        'description' => 'Clear user, group and permission caches together.',
                        'default' => false
                    ],
                    'packages' => [
                        'type' => 'boolean',
                        'description' => 'Clear the installed packages cache.',
                        'default' => false
                    ],
                    'longterm' => [
                        'type' => 'boolean',
                        'description' => 'Clear the long-term cache storage.',
                        'default' => false
                    ]
                ]
            ]
        );
    }
}
