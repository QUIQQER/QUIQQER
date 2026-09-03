<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ActivateUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');

                    $User = self::getUser($user);
                    $User->activate('', Server::getRequestUser());

                    return ['activated' => $User->isActive(), 'user' => self::parseUser($User)];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_activate',
            description: 'Activates one QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
