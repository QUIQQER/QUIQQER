<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateMediaVisibility extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $ids, bool $visible): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $ids = self::validateMediaIds($ids);
                    $Items = [];

                    foreach ($ids as $id) {
                        $Item = self::getMediaItem($project, $id);
                        self::checkMediaPermission($Item, 'quiqqer.projects.media.edit');
                        $Items[] = $Item;
                    }

                    $updated = [];

                    foreach ($Items as $Item) {
                        if ($visible) {
                            $Item->setVisible();
                        } else {
                            $Item->setHidden();
                        }

                        $Item->save(Server::getRequestUser());
                        $updated[] = self::parseMediaItem($Item, true);
                    }

                    return [
                        'updated' => count($updated),
                        'visible' => $visible,
                        'items' => $updated
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_visibility_update',
            description: 'Sets one or more media items to visible or hidden.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'visible'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getMediaIdsSchema(),
                    'visible' => ['type' => 'boolean']
                ]
            ]
        );
    }
}
