<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\SetSiteType
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class SetSiteType extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                string $type,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Site = self::getEditSite($project, $id, $lang);
                    $Site->setAttribute('type', $type);
                    $Site->save(Server::getRequestUser());

                    return [
                        'saved' => true,
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_set_type',
            description: 'Sets the type attribute of one QUIQQER site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'type'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Site ID.', 'minimum' => 1],
                    'type' => ['type' => 'string', 'description' => 'QUIQQER site type.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
