<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DeleteUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.delete');

                    $User = self::getUser($user);
                    $result = self::parseUser($User);
                    $deleted = $User->delete(Server::getRequestUser());

                    return ['deleted' => $deleted, 'user' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_delete',
            description: 'Permanently deletes one manageable QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
