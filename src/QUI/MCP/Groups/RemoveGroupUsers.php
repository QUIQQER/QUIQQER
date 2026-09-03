<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Groups\Everyone;
use QUI\Users\User;
use Throwable;

class RemoveGroupUsers extends AddGroupUsers
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $group, array $users): CallToolResult | array {
                try {
                    self::checkMembershipPermission();

                    $Group = self::getGroup($group);

                    if ($Group instanceof Everyone) {
                        throw new \QUI\Exception('Users cannot be removed from the Everyone group.');
                    }

                    self::checkGroupRemovalPermission($Group);
                    $resolvedUsers = self::resolveUsers($users);
                    $removedUsers = [];
                    $notMembers = [];

                    foreach ($resolvedUsers as $User) {
                        if (!$User->isInGroup($Group->getUUID())) {
                            $notMembers[] = $User->getUUID();
                            continue;
                        }

                        $Group->removeUser($User);
                        self::saveUser($User);
                        $removedUsers[] = $User;
                    }

                    return [
                        'removed' => count($removedUsers),
                        'notMembers' => $notMembers,
                        'group' => self::parseGroup($Group),
                        'users' => array_map(
                            static fn(User $User): array => self::parseUser($User),
                            $removedUsers
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_users_remove',
            description: 'Removes one or more users from a QUIQQER group.',
            inputSchema: self::getMembershipInputSchema()
        );
    }
}
