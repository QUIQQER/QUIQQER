<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class SearchGroups extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $query, int $limit = 50, int $offset = 0): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.view');

                    return self::findGroups($query, $limit, $offset);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_search',
            description: 'Searches QUIQQER groups by name.',
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
