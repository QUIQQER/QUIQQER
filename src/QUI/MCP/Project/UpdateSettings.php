<?php

/**
 * This file contains the \QUI\MCP\Project\UpdateSettings
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateSettings extends AbstractProjectSettingsTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $settings): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project);
                    self::checkProjectSettingsPermission($Project);
                    $result = self::updateSettings($Project, $settings);

                    return [
                        'saved' => true,
                        'project' => self::parseProject($result['project']),
                        'settings' => $result['settings']
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_settings_update',
            description: 'Validates and updates multiple project settings in one project setup run.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'settings'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'settings' => [
                        'type' => 'object',
                        'description' => 'Map of complete setting keys to new scalar values.',
                        'minProperties' => 1,
                        'additionalProperties' => [
                            'type' => ['boolean', 'number', 'string']
                        ]
                    ]
                ]
            ]
        );
    }
}
