<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\Auth\Handler;
use QUI\Users\Auth\WebAuthn\CredentialRepository;
use Throwable;

class ListUserAuthenticators extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');
                    $User = self::getUser($user);
                    $Handler = Handler::getInstance();
                    $authenticators = [];

                    foreach ($Handler->getAvailableAuthenticators() as $authenticator) {
                        try {
                            $Authenticator = $Handler->getAuthenticator($authenticator, $User);
                            $authenticators[] = [
                                'class' => $authenticator,
                                'title' => $Authenticator->getTitle($User->getLocale()),
                                'description' => $Authenticator->getDescription($User->getLocale()),
                                'enabled' => $User->hasAuthenticator($authenticator),
                                'primary' => $Authenticator->isPrimaryAuthentication(),
                                'secondary' => $Authenticator->isSecondaryAuthentication(),
                                'satisfiesSecondary' => $Authenticator->satisfiesSecondaryAuthentication()
                            ];
                        } catch (Throwable) {
                        }
                    }

                    $credentials = array_map(
                        static fn(array $credential): array => self::parseWebAuthnCredential($credential),
                        (new CredentialRepository())->findByUserUuid((string)$User->getUUID())
                    );

                    return [
                        'user' => self::parseUser($User),
                        'authenticators' => $authenticators,
                        'webauthnCredentials' => $credentials
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_authenticators_list',
            description: 'Lists available and enabled authenticators plus sanitized WebAuthn devices of one user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user'],
                'properties' => ['user' => self::getUserIdSchema()]
            ]
        );
    }
}
