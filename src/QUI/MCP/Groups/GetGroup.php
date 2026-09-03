<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetGroup extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.view');

                    return self::parseGroup(self::getGroup($group));
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_get',
            description: 'Returns one QUIQQER group by UUID or legacy ID.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group'],
                'properties' => ['group' => self::getGroupIdSchema()]
            ]
        );
    }
}
