<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DeleteUserAddress extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, int | string $address): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $Address = $User->getAddress($address);
                    $DefaultAddress = $User->getStandardAddress();

                    if ($DefaultAddress->getUUID() === $Address->getUUID()) {
                        throw new QUI\Exception('The default user address cannot be deleted.');
                    }

                    $uuid = $Address->getUUID();
                    $Address->delete();

                    return [
                        'deleted' => true,
                        'addressUuid' => $uuid,
                        'userUuid' => $User->getUUID()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_addresses_delete',
            description: 'Deletes a non-default address of one manageable QUIQQER user.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'address'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'address' => self::getAddressIdSchema()
                ]
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getAddressIdSchema(): array
    {
        return [
            'oneOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'integer', 'minimum' => 1]
            ]
        ];
    }
}
