<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DownloadMediaFolder extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, int | null $maxBytes = null): CallToolResult | array {
                $zipFile = null;

                try {
                    self::checkCorePermission();
                    $Folder = self::getMediaFolder($project, $id);
                    self::checkMediaPermission($Folder, 'quiqqer.projects.media.view');
                    $zipFile = $Folder->createZIP();

                    return [
                        'folder' => self::parseMediaItem($Folder),
                        'download' => self::readDownload(
                            $zipFile,
                            (string)$Folder->getAttribute('name') . '.zip',
                            'application/zip',
                            self::getDownloadLimit($maxBytes)
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                } finally {
                    if (is_string($zipFile) && file_exists($zipFile)) {
                        unlink($zipFile);
                    }
                }
            },
            name: 'quiqqer_media_folder_download',
            description: 'Creates a ZIP archive of a media folder and returns it as Base64 content, limited to 5 MiB.',
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
