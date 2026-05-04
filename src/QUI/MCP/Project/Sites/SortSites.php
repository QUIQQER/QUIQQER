<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\SortSites
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class SortSites extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $parentId,
                array $ids,
                int | null $from = null,
                string | null $sortType = null,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    $Parent = self::getEditSite($project, $parentId, $lang);
                    $Parent->checkPermission('quiqqer.projects.site.edit', Server::getRequestUser());

                    if (!empty($sortType)) {
                        $Parent->setAttribute('order_type', $sortType);
                        $Parent->save(Server::getRequestUser());
                    }

                    $childrenIds = $Parent->getChildrenIds([
                        'active' => '0&1'
                    ]);
                    $orderField = (int)max(0, $from ?? 0);
                    $sorted = [];

                    foreach ($ids as $id) {
                        $id = (int)$id;

                        if (!in_array($id, $childrenIds, true)) {
                            continue;
                        }

                        $orderField++;

                        QUI::getDataBase()->update(
                            $Project->table(),
                            ['order_field' => $orderField],
                            ['id' => $id]
                        );

                        $sorted[] = $id;
                    }

                    $Parent->save(Server::getRequestUser());

                    return [
                        'sorted' => true,
                        'ids' => $sorted,
                        'parent' => self::parseSite($Parent, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_sort',
            description: 'Sorts direct child sites of a parent site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'parentId', 'ids'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'description' => 'Parent site ID.', 'minimum' => 1],
                    'ids' => [
                        'type' => 'array',
                        'description' => 'Ordered direct child site IDs.',
                        'items' => ['type' => 'integer']
                    ],
                    'from' => ['type' => 'integer', 'description' => 'Starting order offset.', 'default' => 0],
                    'sortType' => ['type' => 'string', 'description' => 'Optional parent order_type value.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
