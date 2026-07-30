<?php

/**
 * This file contains the \QUI\MCP\VHost\DeleteVHost
 */

namespace QUI\MCP\VHost;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\VhostManager;
use Throwable;

class DeleteVHost extends AbstractVHostTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $host): CallToolResult | array {
                try {
                    self::checkVHostWritePermission();

                    $Manager = new VhostManager();
                    $vhost = self::parseVHost(
                        $host,
                        self::getVHostOrFail($Manager, $host)
                    );

                    $Manager->removeVhost($host);

                    return [
                        'deleted' => true,
                        'vhost' => $vhost
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_vhosts_delete',
            description: 'Deletes one configured QUIQQER VHost.',
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
