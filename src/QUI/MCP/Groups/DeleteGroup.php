<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DeleteGroup extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group): CallToolResult | array {
                try {
                    self::checkGroupDeletePermission();

                    $Group = self::getGroup($group);
                    $result = self::parseGroup($Group);
                    $Group->delete();

                    return ['deleted' => true, 'group' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_delete',
            description: 'Permanently deletes one group and its child groups. Requires a superuser.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group'],
                'properties' => ['group' => self::getGroupIdSchema()]
            ]
        );
    }
}
