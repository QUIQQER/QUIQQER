<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Manager;
use Throwable;

class RenameProject extends AbstractProjectLifecycleTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $newName): CallToolResult | array {
                try {
                    self::checkProjectSuperUser();
                    self::getProject($project);
                    self::assertProjectNameAvailable($newName);
                    Manager::rename($project, $newName);
                    self::renamePermissionReferences($project, $newName);
                    self::resetProjectManager();

                    return [
                        'renamed' => true,
                        'oldName' => $project,
                        'newName' => $newName,
                        'project' => self::parseProject(self::getProject($newName))
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_rename',
            description: 'Renames one complete QUIQQER project. Requires the MCP request user to be a super-user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'newName'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'newName' => ['type' => 'string', 'minLength' => 3]
                ]
            ]
        );
    }
}
