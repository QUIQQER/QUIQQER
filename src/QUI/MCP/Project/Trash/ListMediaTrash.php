<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListMediaTrash extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int | null $limit = null,
                int | null $offset = null,
                string | null $order = null,
                string | null $direction = null
            ): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    $Project = self::getProject($project);
                    $list = self::getListParams($limit, $offset, $order, $direction);
                    $result = $Project->getMedia()->getTrash()->getList($list['params']);

                    return [
                        'project' => self::parseProject($Project),
                        'items' => $result['data'] ?? [],
                        'total' => (int)($result['total'] ?? 0),
                        'limit' => $list['limit'],
                        'offset' => $list['offset']
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_trash_list',
            description: 'Lists deleted media items in one project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'limit' => ['type' => 'integer', 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'minimum' => 0],
                    'order' => [
                        'type' => 'string',
                        'enum' => [
                            'id',
                            'name',
                            'title',
                            'file',
                            'type',
                            'mime_type',
                            'c_date',
                            'e_date',
                            'deleted_at'
                        ]
                    ],
                    'direction' => ['type' => 'string', 'enum' => ['ASC', 'DESC']]
                ]
            ]
        );
    }
}
