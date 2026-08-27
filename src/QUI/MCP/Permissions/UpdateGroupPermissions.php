<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateGroupPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group, array $permissions): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Group = self::getManagedGroup($group);

                    return self::updatePermissions($Group, $permissions, [
                        'type' => 'group',
                        'uuid' => $Group->getUUID(),
                        'name' => $Group->getName()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_group_update',
            description: 'Partially updates permission values of one QUIQQER group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group', 'permissions'],
                'properties' => [
                    'group' => self::getSubjectIdSchema('group'),
                    'permissions' => self::getPermissionsSchema()
                ]
            ]
        );
    }
}
