<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CreateGroup extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $name, int | string $parent): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.create');

                    $Group = self::getGroup($parent)->createChild(
                        trim($name),
                        Server::getRequestUser()
                    );

                    return ['created' => true, 'group' => self::parseGroup($Group)];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_create',
            description: 'Creates an inactive child group below an existing QUIQQER group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['name', 'parent'],
                'properties' => [
                    'name' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 50],
                    'parent' => self::getGroupIdSchema()
                ]
            ]
        );
    }
}
