<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\GetSite
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class GetSite extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                string | null $lang = null,
                bool | null $load = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Site = self::getProject($project, $lang)->get($id);

                    if ($load === true) {
                        $Site->load();
                    }

                    return self::parseSite($Site, true);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_get',
            description: 'Returns one QUIQQER site by project, language and ID.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Site ID.', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'load' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }
}
