<?php

/**
 * This file contains the \QUI\MCP\Project\Media\UpdateMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Item as MediaItem;
use Throwable;

class UpdateMedia extends AbstractTool
{
    protected const ATTRIBUTES = [
        'name',
        'title',
        'short',
        'description',
        'alt'
    ];

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                array $attributes,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Item = self::getProject($project, $lang)->getMedia()->get($id);
                    $changed = [];

                    foreach ($attributes as $attribute => $value) {
                        if (!in_array($attribute, self::ATTRIBUTES, true)) {
                            continue;
                        }

                        if (!is_scalar($value) && !is_array($value) && $value !== null) {
                            continue;
                        }

                        $Item->setAttribute($attribute, $value);
                        $changed[] = $attribute;
                    }

                    if ($Item instanceof MediaItem) {
                        $Item->save(Server::getRequestUser());
                    } else {
                        $Item->save();
                    }

                    return [
                        'saved' => true,
                        'changedAttributes' => $changed,
                        'item' => self::parseMediaItem($Item, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_update',
            description: 'Updates whitelisted attributes of one media item.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'attributes'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Media item ID.', 'minimum' => 1],
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Whitelisted media attributes.',
                        'additionalProperties' => true
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
