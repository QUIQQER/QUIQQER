<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Manager;
use Throwable;

class DeleteProject extends AbstractProjectLifecycleTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, bool $confirm): CallToolResult | array {
                try {
                    self::checkProjectSuperUser();
                    self::requireConfirmation($confirm);
                    $Project = self::getProject($project);
                    Manager::deleteProject($Project);
                    self::deletePermissionReferences($project);
                    self::resetProjectManager();

                    return [
                        'deleted' => true,
                        'project' => $project
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_delete',
            description: 'Permanently deletes one complete QUIQQER project. Requires super-user access and confirm=true.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'confirm'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'confirm' => self::getConfirmationSchema()
                ]
            ]
        );
    }
}
