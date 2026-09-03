<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateProjectPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                array $permissions,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Project = self::getManagedProject($project, $lang);

                    return self::updatePermissions($Project, $permissions, [
                        'type' => 'project',
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_project_update',
            description: 'Partially updates permission values of one project language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'permissions'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'permissions' => self::getPermissionsSchema(),
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
