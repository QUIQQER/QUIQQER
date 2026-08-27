<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Users\Auth\WebAuthn;
use QUI\Users\Auth\WebAuthn\CredentialRepository;
use Throwable;

class DeleteUserWebAuthnCredential extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, int $credentialId): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $userUuid = (string)$User->getUUID();
                    $Repository = new CredentialRepository();
                    $credential = $Repository->findById($credentialId);

                    if ($credential === null || (string)($credential['userUuid'] ?? '') !== $userUuid) {
                        throw new QUI\Exception('The selected WebAuthn credential does not belong to this user.', 404);
                    }

                    $Repository->deleteForUser($credentialId, $userUuid);
                    $remaining = $Repository->findByUserUuid($userUuid);

                    if ($remaining === []) {
                        try {
                            $User->disableAuthenticator(WebAuthn::class, Server::getRequestUser());
                        } catch (QUI\Users\Exception $Exception) {
                            if ($Exception->getCode() !== 404) {
                                throw $Exception;
                            }
                        }
                    }

                    return [
                        'deleted' => true,
                        'credentialId' => $credentialId,
                        'remaining' => array_map(
                            static fn(array $entry): array => self::parseWebAuthnCredential($entry),
                            $remaining
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_webauthn_credential_delete',
            description: 'Permanently removes one WebAuthn device belonging to a manageable user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'credentialId'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'credentialId' => ['type' => 'integer', 'minimum' => 1]
                ]
            ]
        );
    }
}
