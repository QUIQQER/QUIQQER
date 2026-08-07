<?php

/**
 * This file contains the \QUI\MCP\Project\SetSetting
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class SetSetting extends AbstractProjectSettingsTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $key, mixed $value): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project);
                    self::checkProjectSettingsPermission($Project);
                    $result = self::updateSettings($Project, [$key => $value]);

                    return [
                        'saved' => true,
                        'project' => self::parseProject($result['project']),
                        'setting' => $result['settings'][0]
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_setting_set',
            description: 'Validates and sets one project setting by its complete key.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'key', 'value'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'key' => ['type' => 'string', 'description' => 'Complete project setting key.'],
                    'value' => [
                        'type' => ['boolean', 'number', 'string'],
                        'description' => 'New value. Its JSON type must match the setting definition.'
                    ]
                ]
            ]
        );
    }
}
