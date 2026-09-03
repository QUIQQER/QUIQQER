<?php

namespace QUI\MCP\Forwarding;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\Forwarding;
use Throwable;

class DeleteForwardings extends AbstractForwardingTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (array $sources): CallToolResult | array {
                try {
                    self::checkForwardingPermission();
                    $sources = self::normalizeSources($sources);
                    $forwardings = [];

                    foreach ($sources as $source) {
                        $forwardings[] = self::parseForwarding(
                            $source,
                            self::getForwardingOrFail($source)
                        );
                    }

                    Forwarding::delete($sources);

                    return [
                        'deleted' => count($sources),
                        'forwardings' => $forwardings
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_forwardings_delete',
            description: 'Deletes one or more global QUIQQER Core forwarding rules.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['sources'],
                'properties' => [
                    'sources' => [
                        'type' => 'array',
                        'minItems' => 1,
                        'uniqueItems' => true,
                        'items' => self::getForwardingInputProperties()['source']
                    ]
                ]
            ]
        );
    }
}
