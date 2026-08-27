<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetUserPermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $User = self::getManagedUser($user);

                    return self::getPermissionsResponse($User, [
                        'type' => 'user',
                        'uuid' => $User->getUUID(),
                        'username' => $User->getUsername()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_user_get',
            description: 'Returns the configured permission values of one QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getSubjectIdSchema('user')]
            ]
        );
    }
}
