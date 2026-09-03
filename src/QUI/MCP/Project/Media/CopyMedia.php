<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CopyMedia extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $ids, int $targetFolderId): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $ids = self::validateMediaIds($ids);
                    $Target = self::getMediaFolder($project, $targetFolderId);
                    self::checkMediaPermission($Target, 'quiqqer.projects.media.upload');
                    self::checkMediaPermission($Target, 'quiqqer.projects.media.edit');
                    $Items = [];

                    foreach ($ids as $id) {
                        $Item = self::getMediaItem($project, $id);
                        self::checkMediaPermission($Item, 'quiqqer.projects.media.view');
                        self::validateMoveTarget($Item, $Target);
                        $Items[] = $Item;
                    }

                    $copied = [];
                    $errors = [];

                    foreach ($Items as $Item) {
                        try {
                            $Copy = $Item->copyTo($Target, \QUI\AI\MCP\Server::getRequestUser());
                            $copied[] = [
                                'sourceId' => $Item->getId(),
                                'item' => self::parseMediaItem($Copy, true)
                            ];
                        } catch (Throwable $Exception) {
                            $errors[] = ['id' => $Item->getId(), 'message' => $Exception->getMessage()];
                        }
                    }

                    return [
                        'copied' => count($copied),
                        'targetFolderId' => $targetFolderId,
                        'items' => $copied,
                        'errors' => $errors
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_copy',
            description: 'Copies media files or folders into another media folder.',
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
