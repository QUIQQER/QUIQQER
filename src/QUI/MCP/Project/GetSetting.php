<?php

/**
 * This file contains the \QUI\MCP\Project\GetSetting
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetSetting extends AbstractProjectSettingsTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $key): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project);
                    self::checkProjectSettingsPermission($Project);

                    return [
                        'project' => self::parseProject($Project),
                        'setting' => self::getSetting($Project, $key)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_setting_get',
            description: 'Gets one project setting by its complete key.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'key'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'key' => ['type' => 'string', 'description' => 'Complete project setting key.']
                ]
            ]
        );
    }
}
