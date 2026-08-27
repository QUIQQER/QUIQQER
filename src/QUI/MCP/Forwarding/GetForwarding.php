<?php

namespace QUI\MCP\Forwarding;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetForwarding extends AbstractForwardingTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $source): CallToolResult | array {
                try {
                    self::checkForwardingPermission();
                    $source = self::normalizeSource($source);

                    return [
                        'forwarding' => self::parseForwarding(
                            $source,
                            self::getForwardingOrFail($source)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_forwardings_get',
            description: 'Returns one global QUIQQER Core forwarding rule by its exact source.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['source'],
                'properties' => [
                    'source' => self::getForwardingInputProperties()['source']
                ]
            ]
        );
    }
}
