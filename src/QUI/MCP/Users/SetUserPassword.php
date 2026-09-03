<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class SetUserPassword extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $user,
                string $password,
                bool $forceChange = false
            ): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $User->setPassword($password, Server::getRequestUser());
                    $User->setAttribute('quiqqer.set.new.password', $forceChange);
                    self::saveUser($User);

                    return [
                        'updated' => true,
                        'forceChange' => $forceChange,
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_password_update',
            description: 'Sets a new password without returning it and optionally requires a change after login.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'password'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'password' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 4096],
                    'forceChange' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }
}
