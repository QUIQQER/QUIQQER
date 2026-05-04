<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\ActivateSite
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ActivateSite extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Site = self::getEditSite($project, $id, $lang);
                    $Site->activate(Server::getRequestUser());

                    return [
                        'activated' => true,
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_activate',
            description: 'Activates one QUIQQER site.',
            inputSchema: self::siteIdSchema()
        );
    }

    protected static function siteIdSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['project', 'id'],
            'properties' => [
                'project' => ['type' => 'string', 'description' => 'Project name.'],
                'id' => ['type' => 'integer', 'description' => 'Site ID.', 'minimum' => 1],
                'lang' => ['type' => 'string', 'description' => 'Project language.']
            ]
        ];
    }
}
