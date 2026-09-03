<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateUserAddress extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $user,
                int | string $address,
                array $attributes,
                ?array $mails = null,
                ?array $phones = null
            ): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.edit');
                    $User = self::getUser($user);
                    $Address = $User->getAddress($address);
                    $filtered = self::filterAddressAttributes($attributes);
                    self::updateAddressData($Address, $filtered['attributes'], $mails, $phones);

                    return [
                        'updated' => true,
                        'ignoredAttributes' => $filtered['ignored'],
                        'address' => self::parseAddress($User, $Address)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_addresses_update',
            description: 'Partially updates one user address; mails and phones are replaced only when provided.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['user', 'address', 'attributes'],
                'properties' => [
                    'user' => self::getUserIdSchema(),
                    'address' => self::getAddressIdSchema(),
                    'attributes' => self::getAddressAttributesSchema(),
                    'mails' => self::getAddressMailsSchema(),
                    'phones' => self::getAddressPhonesSchema()
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
