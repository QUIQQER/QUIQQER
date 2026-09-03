<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class MoveMedia extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $ids, int $targetFolderId): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $ids = self::validateMediaIds($ids);
                    $Target = self::getMediaFolder($project, $targetFolderId);
                    self::checkMediaPermission($Target, 'quiqqer.projects.media.edit');
                    $Items = [];

                    foreach ($ids as $id) {
                        $Item = self::getMediaItem($project, $id);
                        self::checkMediaPermission($Item, 'quiqqer.projects.media.edit');
                        self::validateMoveTarget($Item, $Target);
                        $Items[] = $Item;
                    }

                    $moved = [];
                    $errors = [];

                    foreach ($Items as $Item) {
                        try {
                            $Item->moveTo($Target, Server::getRequestUser());
                            $moved[] = self::parseMediaItem($Item, true);
                        } catch (Throwable $Exception) {
                            $errors[] = ['id' => $Item->getId(), 'message' => $Exception->getMessage()];
                        }
                    }

                    return [
                        'moved' => count($moved),
                        'targetFolderId' => $targetFolderId,
                        'items' => $moved,
                        'errors' => $errors
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_move',
            description: 'Moves media files or folders into another media folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'targetFolderId'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getMediaIdsSchema(),
                    'targetFolderId' => ['type' => 'integer', 'minimum' => 1]
                ]
            ]
        );
    }
}
