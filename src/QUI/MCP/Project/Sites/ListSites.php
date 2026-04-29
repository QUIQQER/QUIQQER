<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\ListSites
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Site;
use Throwable;

class ListSites extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                string | null $lang = null,
                int | null $parentId = null,
                int | null $limit = null,
                int | null $offset = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    $Parent = $Project->get($parentId ?: 1);

                    return [
                        'project' => self::parseProject($Project),
                        'parent' => self::parseSite($Parent),
                        'children' => array_map(
                            static fn(Site $Site): array => self::parseSite($Site),
                            $Parent->getChildren([
                                'limit' => self::parseLimit($limit, $offset)
                            ])
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_list',
            description: 'Lists direct child sites for a QUIQQER project site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'parentId' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                    'limit' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0]
                ]
            ]
        );
    }
}
