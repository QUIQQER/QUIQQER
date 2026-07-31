<?php

/**
 * This file contains the \QUI\MCP\VHost\UpdateVHost
 */

namespace QUI\MCP\VHost;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\VhostManager;
use Throwable;

class UpdateVHost extends AbstractVHostTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $host,
                string | null $project = null,
                string | null $rootLanguage = null,
                array | null $pathLanguages = null,
                string | null $template = null,
                string | null $error = null,
                string | null $httpsHost = null
            ): CallToolResult | array {
                try {
                    self::checkVHostWritePermission();

                    $Manager = new VhostManager();
                    $existing = self::getVHostOrFail($Manager, $host);

                    $Manager->editVhost(
                        $host,
                        self::buildVHostData(
                            $existing,
                            $project,
                            $rootLanguage,
                            $pathLanguages,
                            $template,
                            $error,
                            $httpsHost
                        )
                    );

                    return [
                        'updated' => true,
                        'vhost' => self::parseVHost(
                            $host,
                            self::getVHostOrFail($Manager, $host)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_vhosts_update',
            description: 'Updates selected settings of an existing QUIQQER VHost.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['host'],
                'properties' => self::getVHostInputProperties()
            ]
        );
    }
}
