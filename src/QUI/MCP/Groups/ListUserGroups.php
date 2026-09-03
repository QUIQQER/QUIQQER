<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Groups\Group;
use Throwable;

class ListUserGroups extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkMembershipReadPermission();

                    $User = self::getUser($user);

                    return [
                        'user' => [
                            'uuid' => $User->getUUID(),
                            'username' => $User->getUsername()
                        ],
                        'groups' => array_map(
                            static fn(Group $Group): array => self::parseGroup($Group),
                            $User->getGroups()
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_groups_list',
            description: 'Lists all groups assigned to one QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
