<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetGroupPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Group = self::getManagedGroup($group);

                    return self::getPermissionsResponse($Group, [
                        'type' => 'group',
                        'uuid' => $Group->getUUID(),
                        'name' => $Group->getName()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_group_get',
            description: 'Returns the configured permission values of one QUIQQER group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group'],
                'properties' => ['group' => self::getSubjectIdSchema('group')]
            ]
        );
    }
}
