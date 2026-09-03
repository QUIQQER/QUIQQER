<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetMediaFolderSize extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, bool $force = false): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Folder = self::getMediaFolder($project, $id);
                    self::checkMediaPermission($Folder, 'quiqqer.projects.media.view');
                    $size = \QUI\Utils\System\Folder::getFolderSize($Folder->getFullPath(), $force);

                    return [
                        'folder' => self::parseMediaItem($Folder),
                        'sizeBytes' => $size,
                        'sizeKnown' => $size !== null,
                        'forced' => $force
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_folder_size_get',
            description: 'Returns the recursive filesystem size of one media folder in bytes.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'force' => [
                        'type' => 'boolean',
                        'default' => false,
                        'description' => 'Calculate immediately instead of returning only a cached size.'
                    ]
                ]
            ]
        );
    }
}
