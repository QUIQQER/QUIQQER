<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateMediaPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, array $permissions): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Item = self::getManagedMedia($project, $id);

                    return self::updatePermissions($Item, $permissions, [
                        'type' => 'media',
                        'project' => $Item->getProject()->getName(),
                        'id' => $Item->getId()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_media_update',
            description: 'Partially updates permission values of one media item.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'permissions'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'permissions' => self::getPermissionsSchema()
                ]
            ]
        );
    }
}
