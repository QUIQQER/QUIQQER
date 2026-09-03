<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\Auth\Handler;
use Throwable;

class DisableUserAuthenticator extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, string $authenticator): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $Authenticator = Handler::getInstance()->getAuthenticator($authenticator, $User);

                    if (!$Authenticator->isSecondaryAuthentication()) {
                        throw new QUI\Exception('Only secondary authenticators can be disabled with this tool.');
                    }

                    $wasEnabled = $User->hasAuthenticator($authenticator);
                    $User->disableAuthenticator($authenticator, Server::getRequestUser());

                    return [
                        'disabled' => $wasEnabled,
                        'authenticator' => $authenticator,
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_authenticator_disable',
            description: 'Administratively disables one secondary authenticator for a manageable user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'authenticator'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'authenticator' => ['type' => 'string', 'minLength' => 1]
                ]
            ]
        );
    }
}
