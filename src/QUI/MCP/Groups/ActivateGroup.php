<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ActivateGroup extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.edit');

                    $Group = self::getGroup($group);
                    $Group->activate();

                    return ['activated' => $Group->isActive(), 'group' => self::parseGroup($Group)];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_activate',
            description: 'Activates one QUIQQER group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group'],
                'properties' => ['group' => self::getGroupIdSchema()]
            ]
        );
    }
}
