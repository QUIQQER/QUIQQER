<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\User;
use Throwable;

class CreateUser extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $username, array | null $attributes = null): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.create');

                    $filtered = self::filterUserAttributes($attributes ?? []);

                    if (array_key_exists('username', $filtered['attributes'])) {
                        unset($filtered['attributes']['username']);
                        $filtered['ignored'][] = 'username';
                    }

                    $createAttributes = array_merge(
                        ['username' => trim($username)],
                        $filtered['attributes']
                    );
                    $User = QUI::getUsers()->createChildWithAttributes(
                        $createAttributes,
                        Server::getRequestUser()
                    );

                    if (!$User instanceof User) {
                        throw new QUI\Exception('The new account is not a manageable QUIQQER user.');
                    }

                    return [
                        'created' => true,
                        'ignoredAttributes' => $filtered['ignored'],
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_create',
            description: 'Creates a QUIQQER user with selected non-security attributes.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['username'],
                'properties' => [
                    'username' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 50],
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Optional user profile attributes.',
                        'additionalProperties' => true
                    ]
                ]
            ]
        );
    }
}
