<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListPermissions extends AbstractPermissionTool
{
    private const AREAS = ['global', 'user', 'groups', 'project', 'site', 'media'];

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string | null $area = null): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();

                    if ($area !== null && !in_array($area, self::AREAS, true)) {
                        throw new QUI\Exception('Unknown permission area: ' . $area);
                    }

                    return [
                        'area' => $area,
                        'permissions' => self::parsePermissionDefinitions(
                            self::getPermissionDefinitions($area)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_list',
            description: 'Lists available QUIQQER permission definitions, optionally filtered by area.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => [
                    'area' => [
                        'type' => ['string', 'null'],
                        'enum' => [...self::AREAS, null]
                    ]
                ]
            ]
        );
    }
}
