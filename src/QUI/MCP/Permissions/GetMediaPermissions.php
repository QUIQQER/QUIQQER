<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetMediaPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Item = self::getManagedMedia($project, $id);

                    return self::getPermissionsResponse($Item, [
                        'type' => 'media',
                        'project' => $Item->getProject()->getName(),
                        'id' => $Item->getId()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_media_get',
            description: 'Returns configured permission values of one media item.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1]
                ]
            ]
        );
    }
}
