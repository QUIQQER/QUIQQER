<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');

                    return self::parseUser(self::getUser($user));
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_get',
            description: 'Returns one manageable QUIQQER user by UUID or legacy ID.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
