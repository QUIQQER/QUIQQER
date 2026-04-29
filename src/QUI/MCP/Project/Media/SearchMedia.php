<?php

/**
 * This file contains the \QUI\MCP\Project\Media\SearchMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class SearchMedia extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                string $query,
                string | null $lang = null,
                int | null $limit = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    $Media = $Project->getMedia();
                    $ids = $Media->getChildrenIds([
                        'where_or' => [
                            'name' => ['type' => '%LIKE%', 'value' => $query],
                            'title' => ['type' => '%LIKE%', 'value' => $query],
                            'short' => ['type' => '%LIKE%', 'value' => $query]
                        ],
                        'where' => [
                            'deleted' => 0
                        ],
                        'limit' => '0,' . self::sanitizeLimit($limit)
                    ]);
                    $result = [];

                    foreach ($ids as $id) {
                        try {
                            $result[] = self::parseMediaItem($Media->get((int)$id));
                        } catch (Throwable $Exception) {
                            QUI\System\Log::writeDebugException($Exception);
                        }
                    }

                    return $result;
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_search',
            description: 'Searches QUIQQER media items by name, title and short description.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'query'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'query' => ['type' => 'string', 'description' => 'Search term.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'limit' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100]
                ]
            ]
        );
    }
}
