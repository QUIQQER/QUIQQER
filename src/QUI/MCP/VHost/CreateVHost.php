<?php

/**
 * This file contains the \QUI\MCP\VHost\CreateVHost
 */

namespace QUI\MCP\VHost;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\System\VhostManager;
use Throwable;

class CreateVHost extends AbstractVHostTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $host,
                string $project,
                string $rootLanguage,
                array | null $pathLanguages = null,
                string | null $template = null,
                string | null $error = null,
                string | null $httpsHost = null,
                string | null $wwwRedirect = null
            ): CallToolResult | array {
                try {
                    self::checkVHostWritePermission();

                    $Manager = new VhostManager();
                    $host = $Manager->addVhost($host);

                    try {
                        $Manager->editVhost(
                            $host,
                            self::buildVHostData(
                                [],
                                $project,
                                $rootLanguage,
                                $pathLanguages ?? [],
                                $template ?? '',
                                $error ?? '',
                                $httpsHost ?? '',
                                $wwwRedirect ?? ''
                            )
                        );
                    } catch (Throwable $Exception) {
                        try {
                            $Manager->removeVhost($host);
                        } catch (Throwable) {
                        }

                        throw $Exception;
                    }

                    return [
                        'created' => true,
                        'vhost' => self::parseVHost(
                            $host,
                            self::getVHostOrFail($Manager, $host)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_vhosts_create',
            description: 'Creates a VHost with one Root-language and optional Path-languages.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['host', 'project', 'rootLanguage'],
                'properties' => self::getVHostInputProperties()
            ]
        );
    }
}
