<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListUsers extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $limit = 50, int $offset = 0): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');

                    return self::findUsers(null, $limit, $offset);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_list',
            description: 'Lists QUIQQER users without exposing authentication secrets.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50],
                    'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0]
                ]
            ]
        );
    }
}
