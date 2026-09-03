<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListGroups extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int $limit = 50, int $offset = 0): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.view');

                    return self::findGroups(null, $limit, $offset);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_list',
            description: 'Lists QUIQQER groups with hierarchy and membership counts.',
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
