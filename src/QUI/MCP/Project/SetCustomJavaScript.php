<?php

/**
 * This file contains the \QUI\MCP\Project\SetCustomJavaScript
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use Throwable;

class SetCustomJavaScript extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $javascript, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    Permission::checkProjectPermission(
                        'quiqqer.projects.editCustomJS',
                        $Project,
                        Server::getRequestUser()
                    );

                    $Project->setCustomJavaScript($javascript);

                    return [
                        'saved' => true,
                        'project' => self::parseProject($Project)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_custom_javascript_set',
            description: 'Sets the custom JavaScript of a QUIQQER project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'javascript'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'javascript' => ['type' => 'string', 'description' => 'Custom JavaScript content.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
