<?php

/**
 * This file contains the \QUI\MCP\VHost\ListVHosts
 */

namespace QUI\MCP\VHost;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\VhostManager;
use Throwable;

use function ctype_digit;
use function ksort;

class ListVHosts extends AbstractVHostTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Manager = new VhostManager();
                    $config = $Manager->getList();
                    $vhosts = [];

                    ksort($config);

                    foreach ($config as $host => $data) {
                        if (ctype_digit((string)$host)) {
                            continue;
                        }

                        $vhosts[] = self::parseVHost((string)$host, $data);
                    }

                    return [
                        'count' => count($vhosts),
                        'vhosts' => $vhosts
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_vhosts_list',
            description: 'Lists configured QUIQQER VHosts with Root- and Path-language ownership.'
        );
    }
}
