<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetUserAddress extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user, int | string $address): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');
                    $User = self::getUser($user);

                    return self::parseAddress($User, $User->getAddress($address));
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_addresses_get',
            description: 'Returns one address belonging to a manageable QUIQQER user.',
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
            'description' => 'Address UUID or legacy numeric ID.',
            'oneOf' => [
                ['type' => 'string', 'minLength' => 1],
                ['type' => 'integer', 'minimum' => 1]
            ]
        ];
    }
}
