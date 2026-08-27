<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, array $attributes): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');

                    $User = self::getUser($user);
                    $filtered = self::filterUserAttributes($attributes);

                    foreach ($filtered['attributes'] as $attribute => $value) {
                        $User->setAttribute($attribute, $value);
                    }

                    self::saveUser($User);

                    return [
                        'saved' => true,
                        'changedAttributes' => array_keys($filtered['attributes']),
                        'ignoredAttributes' => $filtered['ignored'],
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_update',
            description: 'Updates whitelisted non-security attributes of one QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'attributes'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Whitelisted user attributes.',
                        'additionalProperties' => true
                    ]
                ]
            ]
        );
    }
}
