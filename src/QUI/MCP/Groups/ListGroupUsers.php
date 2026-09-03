<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListGroupUsers extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $group,
                int $limit = 50,
                int $offset = 0
            ): CallToolResult | array {
                try {
                    self::checkMembershipReadPermission();

                    $limit = max(1, min(100, $limit));
                    $offset = max(0, $offset);
                    $Group = self::getGroup($group);
                    $params = [
                        'limit' => $offset . ',' . $limit,
                        'order' => 'username ASC'
                    ];
                    $users = [];

                    foreach ($Group->getUsers($params) as $row) {
                        if (!isset($row['uuid'])) {
                            continue;
                        }

                        try {
                            $users[] = self::parseUser(self::getUser((string)$row['uuid']));
                        } catch (QUI\Exception) {
                        }
                    }

                    return [
                        'group' => self::parseGroup($Group),
                        'users' => $users,
                        'total' => $Group->countUser(),
                        'limit' => $limit,
                        'offset' => $offset
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_users_list',
            description: 'Lists users assigned to one QUIQQER group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group'],
                'properties' => [
                    'group' => self::getGroupIdSchema(),
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'default' => 50],
                    'offset' => ['type' => 'integer', 'minimum' => 0, 'default' => 0]
                ]
            ]
        );
    }
}
