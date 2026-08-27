<?php

namespace QUI\MCP\Forwarding;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\Forwarding;
use Throwable;

class UpdateForwarding extends AbstractForwardingTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $source,
                string $target,
                int $httpCode = 301
            ): CallToolResult | array {
                try {
                    self::checkForwardingPermission();
                    $source = self::normalizeSource($source);
                    $target = self::normalizeTarget($target);
                    $httpCode = self::normalizeHttpCode($httpCode);
                    self::getForwardingOrFail($source);
                    Forwarding::update($source, $target, $httpCode);

                    return [
                        'updated' => true,
                        'forwarding' => self::parseForwarding(
                            $source,
                            self::getForwardingOrFail($source)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_forwardings_update',
            description: 'Updates target and HTTP status code of one global forwarding rule.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['source', 'target'],
                'properties' => self::getForwardingInputProperties()
            ]
        );
    }
}
