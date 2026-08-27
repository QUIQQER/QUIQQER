<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CreateUserAddress extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $user,
                array $attributes,
                ?array $mails = null,
                ?array $phones = null,
                bool $setDefault = false
            ): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $filtered = self::filterAddressAttributes($attributes);
                    $Address = $User->addAddress($filtered['attributes'], Server::getRequestUser());
                    self::updateAddressData($Address, $filtered['attributes'], $mails, $phones);

                    if ($setDefault) {
                        $User->setAttribute('address', $Address->getUUID());
                        self::saveUser($User);
                    }

                    return [
                        'created' => true,
                        'ignoredAttributes' => $filtered['ignored'],
                        'address' => self::parseAddress($User, $Address)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_addresses_create',
            description: 'Creates an address for one manageable QUIQQER user.',
            inputSchema: self::getCreateSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getCreateSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['user', 'attributes'],
            'properties' => [
                'user' => self::getUserIdSchema(),
                'attributes' => self::getAddressAttributesSchema(),
                'mails' => self::getAddressMailsSchema(),
                'phones' => self::getAddressPhonesSchema(),
                'setDefault' => ['type' => 'boolean', 'default' => false]
            ]
        ];
    }
}
