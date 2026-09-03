<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\User;
use Throwable;

class AddGroupUsers extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group, array $users): CallToolResult | array {
                try {
                    self::checkMembershipPermission();

                    $Group = self::getGroup($group);
                    $resolvedUsers = self::resolveUsers($users);
                    $addedUsers = [];
                    $alreadyMembers = [];

                    foreach ($resolvedUsers as $User) {
                        if ($User->isInGroup($Group->getUUID())) {
                            $alreadyMembers[] = $User->getUUID();
                            continue;
                        }

                        $Group->addUser($User);
                        self::saveUser($User);
                        $addedUsers[] = $User;
                    }

                    return [
                        'added' => count($addedUsers),
                        'alreadyMembers' => $alreadyMembers,
                        'group' => self::parseGroup($Group),
                        'users' => array_map(
                            static fn(User $User): array => self::parseUser($User),
                            $addedUsers
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_users_add',
            description: 'Adds one or more users to a QUIQQER group.',
            inputSchema: self::getMembershipInputSchema()
        );
    }

    /**
     * @param array<array-key, mixed> $users
     * @return array<int, User>
     */
    protected static function resolveUsers(array $users): array
    {
        if ($users === []) {
            throw new \QUI\Exception('At least one user must be provided.');
        }

        $result = [];

        foreach ($users as $user) {
            if (!is_int($user) && !is_string($user)) {
                throw new \QUI\Exception('Every user identifier must be a UUID or numeric ID.');
            }

            $User = self::getUser($user);
            $result[(string)$User->getUUID()] = $User;
        }

        return array_values($result);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getMembershipInputSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['group', 'users'],
            'properties' => [
                'group' => self::getGroupIdSchema(),
                'users' => [
                    'type' => 'array',
                    'minItems' => 1,
                    'items' => self::getUserIdSchema()
                ]
            ]
        ];
    }
}
