<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class SearchUsers extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $query, int $limit = 50, int $offset = 0): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');

                    return self::findUsers($query, $limit, $offset);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_search',
            description: 'Searches users by UUID, username, email, first name and last name.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['query'],
                'properties' => [
                    'query' => ['type' => 'string', 'minLength' => 1],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50],
                    'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0]
                ]
            ]
        );
    }
}
