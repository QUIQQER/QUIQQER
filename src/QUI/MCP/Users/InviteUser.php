<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\Invite;
use QUI\Users\User;
use Throwable;

class InviteUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $email, ?array $groups = null): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.create');
                    self::checkPermission('quiqqer.admin.users.send_mail');

                    if (filter_var(trim($email), FILTER_VALIDATE_EMAIL) === false) {
                        throw new QUI\Exception('A valid invitation e-mail address is required.');
                    }

                    $groupIds = self::resolveInviteGroups($groups ?? []);
                    $User = (new Invite())->invite(trim($email), $groupIds);

                    if (!$User instanceof User) {
                        throw new QUI\Exception('The invited account is not a manageable QUIQQER user.');
                    }

                    return [
                        'invited' => true,
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_invite',
            description: 'Creates, activates and e-mails an invited user with an initial one-time password.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['email'],
                'properties' => [
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'groups' => [
                        'type' => ['array', 'null'],
                        'uniqueItems' => true,
                        'items' => [
                            'oneOf' => [
                                ['type' => 'string', 'minLength' => 1],
                                ['type' => 'integer', 'minimum' => 0]
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * @param array<array-key, mixed> $groups
     * @return array<int, int>
     */
    protected static function resolveInviteGroups(array $groups): array
    {
        $result = [];

        foreach ($groups as $group) {
            if (!is_int($group) && !is_string($group)) {
                throw new QUI\Exception('Every invitation group must be a UUID or numeric ID.');
            }

            $Group = QUI::getGroups()->get($group);
            $result[(string)$Group->getUUID()] = $Group->getId();
        }

        return array_values($result);
    }
}
