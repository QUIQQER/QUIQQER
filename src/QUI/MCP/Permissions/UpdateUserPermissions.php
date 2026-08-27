<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateUserPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, array $permissions): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $User = self::getManagedUser($user);

                    return self::updatePermissions($User, $permissions, [
                        'type' => 'user',
                        'uuid' => $User->getUUID(),
                        'username' => $User->getUsername()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_user_update',
            description: 'Partially updates permission values of one QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'permissions'],
                'properties' => [
                    'user' => self::getSubjectIdSchema('user'),
                    'permissions' => self::getPermissionsSchema()
                ]
            ]
        );
    }
}
