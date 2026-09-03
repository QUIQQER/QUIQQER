<?php

namespace QUI\MCP\Users;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListUserAddresses extends AbstractUserTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (int | string $user): CallToolResult | array {
                try {
                    self::checkUserPermission('quiqqer.admin.users.view');
                    $User = self::getUser($user);
                    $addresses = [];

                    foreach ($User->getAddressList() as $Address) {
                        $addresses[] = self::parseAddress($User, $Address);
                    }

                    return [
                        'user' => self::parseUser($User),
                        'count' => count($addresses),
                        'addresses' => $addresses
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_users_addresses_list',
            description: 'Lists all addresses of one manageable QUIQQER user.',
            inputSchema: self::getUserInputSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getUserInputSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['user'],
            'properties' => ['user' => self::getUserIdSchema()]
        ];
    }
}
