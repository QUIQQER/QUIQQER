<?php

/**
 * This file contains the \QUI\MCP\VHost\GetVHost
 */

namespace QUI\MCP\VHost;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\VhostManager;
use Throwable;

class GetVHost extends AbstractVHostTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $host): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Manager = new VhostManager();

                    return [
                        'vhost' => self::parseVHost(
                            $host,
                            self::getVHostOrFail($Manager, $host)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_vhosts_get',
            description: 'Returns one configured QUIQQER VHost.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['host'],
                'properties' => [
                    'host' => ['type' => 'string', 'description' => 'VHost domain name.']
                ]
            ]
        );
    }
}
