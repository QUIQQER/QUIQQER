<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Folder;
use Throwable;

class RenameMedia extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, string $name): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    if (trim($name) === '') {
                        throw new QUI\Exception('Media name must not be empty.');
                    }

                    $Item = self::getMediaItem($project, $id);

                    if (!$Item instanceof Folder) {
                        $pathExtension = $Item->getPathinfo(PATHINFO_EXTENSION);
                        $extension = is_string($pathExtension) ? $pathExtension : '';
                        $suffix = '.' . $extension;

                        if ($extension !== '' && str_ends_with(mb_strtolower($name), mb_strtolower($suffix))) {
                            $name = mb_substr($name, 0, -mb_strlen($suffix));
                        }
                    }

                    if (trim($name) === '') {
                        throw new QUI\Exception('Media name must not be empty.');
                    }

                    self::checkMediaPermission($Item, 'quiqqer.projects.media.edit');
                    $Item->rename($name, Server::getRequestUser());

                    return [
                        'renamed' => true,
                        'item' => self::parseMediaItem($Item, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_rename',
            description: 'Renames one media file or folder using the media filesystem operation.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'name'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'name' => ['type' => 'string', 'minLength' => 1]
                ]
            ]
        );
    }
}
