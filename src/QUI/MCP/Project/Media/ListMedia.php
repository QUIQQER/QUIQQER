<?php

/**
 * This file contains the \QUI\MCP\Project\Media\ListMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Interfaces\Projects\Media\File as MediaFile;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Folder;
use Throwable;

class ListMedia extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int | null $parentId = null,
                string | null $lang = null,
                int | null $limit = null,
                int | null $offset = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    $Folder = $Project->getMedia()->get($parentId ?: 1);

                    if (!$Folder instanceof Folder) {
                        throw new QUI\Exception('Media item is not a folder.');
                    }

                    return [
                        'project' => self::parseProject($Project),
                        'folder' => self::parseMediaItem($Folder),
                        'children' => array_map(
                            static fn(MediaFile $Item): array => self::parseMediaItem($Item),
                            $Folder->getChildren([
                                'limit' => self::parseLimit($limit, $offset)
                            ])
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_list',
            description: 'Lists direct children of a QUIQQER media folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'default' => 1, 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'limit' => ['type' => 'integer', 'default' => 50, 'minimum' => 1, 'maximum' => 100],
                    'offset' => ['type' => 'integer', 'default' => 0, 'minimum' => 0]
                ]
            ]
        );
    }
}
