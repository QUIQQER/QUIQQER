<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetMediaFolderPreview extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $folderId): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Folder = self::getMediaFolder($project, $folderId);
                    self::checkMediaPermission($Folder, 'quiqqer.projects.media.view');
                    $Preview = $Folder->firstImage();
                    self::checkMediaPermission($Preview, 'quiqqer.projects.media.view');

                    return [
                        'folder' => self::parseMediaItem($Folder),
                        'preview' => self::parseMediaItem($Preview, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_folder_preview_get',
            description: 'Returns the image currently used as the first preview image of a media folder.',
            inputSchema: self::getFolderSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getFolderSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['project', 'folderId'],
            'properties' => [
                'project' => ['type' => 'string', 'minLength' => 1],
                'folderId' => ['type' => 'integer', 'minimum' => 1]
            ]
        ];
    }
}
