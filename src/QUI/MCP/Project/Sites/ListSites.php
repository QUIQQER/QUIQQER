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
use QUI\Projects\Site\PermissionDenied;
use Throwable;

class ListSites extends AbstractTool
{
    /**
     * @param array<Site> $children
     * @return array{sites: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    protected static function parseChildren(array $children): array
    {
        $sites = [];
        $skipped = [];

        foreach ($children as $Site) {
            if ($Site instanceof PermissionDenied) {
                $skipped[] = [
                    'id' => $Site->getId(),
                    'reason' => 'permission_denied'
                ];

                continue;
            }

            $sites[] = self::parseSite($Site);
        }

        return [
            'sites' => $sites,
            'skipped' => $skipped
        ];
    }

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
                    $Parent = self::getEditSite($project, $parentId ?: 1, $lang);

                    $children = $Parent->getChildren([
                        'limit' => self::parseLimit($limit, $offset)
                    ]);

                    if (!is_array($children)) {
                        $children = [];
                    }

                    $children = self::parseChildren($children);

                    return [
                        'project' => self::parseProject($Project),
                        'parent' => self::parseSite($Parent),
                        'children' => $children['sites'],
                        'skippedChildren' => $children['skipped']
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
