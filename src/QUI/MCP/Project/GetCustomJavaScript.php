<?php

/**
 * This file contains the \QUI\MCP\Project\GetCustomJavaScript
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use Throwable;

class GetCustomJavaScript extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    Permission::checkProjectPermission(
                        'quiqqer.projects.editCustomJS',
                        $Project,
                        Server::getRequestUser()
                    );

                    return [
                        'project' => self::parseProject($Project),
                        'javascript' => $Project->getCustomJavaScript()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_custom_javascript_get',
            description: 'Returns the custom JavaScript of a QUIQQER project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
