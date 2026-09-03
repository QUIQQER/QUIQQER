<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Site\Edit;
use Throwable;

class ListSiteTrash extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                string | null $lang = null,
                int | null $limit = null,
                int | null $offset = null,
                string | null $order = null,
                string | null $direction = null
            ): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    $Project = self::getProject($project, $lang);
                    $list = self::getListParams($limit, $offset, $order, $direction);
                    $order = match ($order) {
                        'name', 'title', 'e_date', 'deleted_at' => $order,
                        default => 'e_date'
                    };
                    $direction = $direction === 'ASC' ? 'ASC' : 'DESC';
                    $where = ['active' => -1, 'deleted' => 1];
                    $siteIds = $Project->getSitesIds([
                        'where' => $where,
                        'limit' => $list['params']['limit'],
                        'order' => $order . ' ' . $direction
                    ]);
                    $count = $Project->getSitesIds(['where' => $where, 'count' => true]);
                    $items = [];

                    foreach ($siteIds as $siteData) {
                        $Site = new Edit($Project, (int)$siteData['id']);
                        $items[] = [
                            'id' => $Site->getId(),
                            'name' => $Site->getAttribute('name'),
                            'title' => $Site->getAttribute('title'),
                            'type' => $Site->getAttribute('type'),
                            'editDate' => $Site->getAttribute('e_date'),
                            'deletedAt' => $Site->getAttribute('deleted_at')
                                ?: $Site->getAttribute('e_date'),
                            'editUser' => $Site->getAttribute('e_user')
                        ];
                    }

                    return [
                        'project' => self::parseProject($Project),
                        'items' => $items,
                        'total' => (int)($count[0]['count'] ?? 0),
                        'limit' => $list['limit'],
                        'offset' => $list['offset']
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_trash_list',
            description: 'Lists deleted sites in one project language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'lang' => ['type' => ['string', 'null']],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'minimum' => 0],
                    'order' => [
                        'type' => 'string',
                        'enum' => ['name', 'title', 'e_date', 'deleted_at']
                    ],
                    'direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC']]
                ]
            ]
        );
    }
}
