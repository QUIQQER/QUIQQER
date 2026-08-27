<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Folder;
use Throwable;

class DownloadMedia extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, int | null $maxBytes = null): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Item = self::getMediaItem($project, $id);

                    if ($Item instanceof Folder) {
                        throw new QUI\Exception('Use quiqqer_media_folder_download for folders.');
                    }

                    self::checkMediaPermission($Item, 'quiqqer.projects.media.view');

                    return [
                        'item' => self::parseMediaItem($Item),
                        'download' => self::readDownload(
                            $Item->getFullPath(),
                            basename($Item->getFullPath()),
                            (string)$Item->getAttribute('mime_type'),
                            self::getDownloadLimit($maxBytes)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_download',
            description: 'Returns one media file as Base64 content, limited to 5 MiB.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'maxBytes' => [
                        'type' => 'integer',
                        'minimum' => 1,
                        'maximum' => self::MAX_DOWNLOAD_BYTES,
                        'default' => self::MAX_DOWNLOAD_BYTES
                    ]
                ]
            ]
        );
    }
}
