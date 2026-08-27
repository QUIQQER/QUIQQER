<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Image;
use Throwable;

class UpdateMediaFolderPreview extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $folderId, int $imageId): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Folder = self::getMediaFolder($project, $folderId);
                    self::checkMediaPermission($Folder, 'quiqqer.projects.media.edit');
                    $Image = self::getMediaItem($project, $imageId);

                    if (!$Image instanceof Image) {
                        throw new QUI\Exception('The selected preview media item is not an image.');
                    }

                    self::checkMediaPermission($Image, 'quiqqer.projects.media.edit');
                    UpdateMediaOrder::applyMediaOrder($Folder, [$imageId]);

                    return [
                        'updated' => true,
                        'folder' => self::parseMediaItem($Folder),
                        'preview' => self::parseMediaItem($Image, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_folder_preview_update',
            description: 'Selects a direct child image as a media folder preview by moving it to priority one.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'folderId', 'imageId'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'folderId' => ['type' => 'integer', 'minimum' => 1],
                    'imageId' => ['type' => 'integer', 'minimum' => 1]
                ]
            ]
        );
    }
}
