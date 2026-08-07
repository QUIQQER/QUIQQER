<?php

/**
 * This file contains the \QUI\MCP\Project\ListSettings
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListSettings extends AbstractProjectSettingsTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, ?string $prefix = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project);
                    self::checkProjectSettingsPermission($Project);
                    $settings = self::getSettings($Project, $prefix);

                    return [
                        'project' => self::parseProject($Project),
                        'count' => count($settings),
                        'settings' => $settings
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_settings_list',
            description: 'Lists project settings with current values, defaults, normalized types and source packages.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'prefix' => [
                        'type' => 'string',
                        'description' => 'Optional setting-key prefix, for example templatePresentation.settings.'
                    ]
                ]
            ]
        );
    }
}
