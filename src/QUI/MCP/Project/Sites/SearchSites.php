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
use QUI\Projects\Site\Edit;
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
                    $limit = self::sanitizeLimit($limit);
                    $projects = empty($project)
                        ? QUI::getProjectManager()->getProjects(true)
                        : [self::getProject($project, $lang)];

                    foreach ($projects as $Project) {
                        foreach (self::searchProject($Project, $query, $limit - count($results)) as $Site) {
                            $results[] = self::parseSite($Site);

                            if (count($results) >= $limit) {
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

    /**
     * @return Site[]
     */
    protected static function searchProject(Project $Project, string $query, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $Connection = QUI::getDataBaseConnection();
        $Platform = $Connection->getDatabasePlatform();
        $search = "%" . $query . "%";
        $QueryBuilder = $Connection->createQueryBuilder();
        $ExpressionBuilder = $QueryBuilder->expr();
        $entries = $QueryBuilder
            ->select($Platform->quoteSingleIdentifier("id"))
            ->from($Platform->quoteSingleIdentifier($Project->table()))
            ->where($ExpressionBuilder->or(
                $Platform->quoteSingleIdentifier("name") . " LIKE :search",
                $Platform->quoteSingleIdentifier("title") . " LIKE :search",
                $Platform->quoteSingleIdentifier("short") . " LIKE :search",
                $Platform->quoteSingleIdentifier("content") . " LIKE :search"
            ))
            ->andWhere($Platform->quoteSingleIdentifier("deleted") . " = :deleted")
            ->setParameter("search", $search)
            ->setParameter("deleted", 0)
            ->setMaxResults($limit)
            ->executeQuery()
            ->fetchAllAssociative();

        $result = [];

        foreach ($entries as $entry) {
            try {
                $result[] = new Edit($Project, (int)$entry['id']);
            } catch (Throwable $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        return $result;
    }
}
