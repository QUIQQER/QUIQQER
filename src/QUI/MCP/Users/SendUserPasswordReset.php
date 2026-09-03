<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\Auth\Handler;
use Throwable;

class SendUserPasswordReset extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.send_mail');
                    $User = self::getUser($user);
                    $email = $User->getAttribute('email');

                    if (!is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
                        throw new QUI\Exception('The selected user has no valid e-mail address.');
                    }

                    Handler::getInstance()->sendPasswordResetVerificationMail($User);

                    return [
                        'sent' => true,
                        'user' => self::parseUser($User)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_password_reset_send',
            description: 'Sends the configured password-reset verification mail to one manageable user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
