<?php

/**
 * This file contains the \QUI\MCP\Project\SetCustomCSS
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use Throwable;

class SetCustomCSS extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $css, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    Permission::checkProjectPermission(
                        'quiqqer.projects.editCustomCSS',
                        $Project,
                        Server::getRequestUser()
                    );

                    $Project->setCustomCSS($css);

                    return [
                        'saved' => true,
                        'project' => self::parseProject($Project)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_project_custom_css_set',
            description: 'Sets the custom CSS of a QUIQQER project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'css'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'css' => ['type' => 'string', 'description' => 'Custom CSS content.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
