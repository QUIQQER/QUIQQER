<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\SearchSites
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Project;
use QUI\Projects\Site;
use Throwable;

class SearchSites extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $query,
                string | null $project = null,
                string | null $lang = null,
                int | null $limit = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $results = [];
                    $projects = empty($project)
                        ? QUI::getProjectManager()->getProjects(true)
                        : [self::getProject($project, $lang)];

                    foreach ($projects as $Project) {
                        if (!$Project instanceof Project) {
                            continue;
                        }

                        foreach ($Project->search($query, ['name', 'title', 'short', 'content']) as $Site) {
                            if (!$Site instanceof Site) {
                                continue;
                            }

                            $results[] = self::parseSite($Site);

                            if (count($results) >= self::sanitizeLimit($limit)) {
                                return $results;
                            }
                        }
                    }

                    return $results;
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_search',
            description: 'Searches QUIQQER sites by name, title, short description and content.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['query'],
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'Search term.'],
                    'project' => ['type' => 'string', 'description' => 'Optional project name.'],
                    'lang' => ['type' => 'string', 'description' => 'Optional project language.'],
                    'limit' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100]
                ]
            ]
        );
    }
}
