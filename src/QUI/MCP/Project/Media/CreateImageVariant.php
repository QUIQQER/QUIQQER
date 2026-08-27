<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Image;
use Throwable;

class CreateImageVariant extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                ?int $maxWidth = null,
                ?int $maxHeight = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Item = self::getMediaItem($project, $id);

                    if (!$Item instanceof Image) {
                        throw new QUI\Exception('Media item ' . $id . ' is not an image.');
                    }

                    self::checkMediaPermission($Item, 'quiqqer.projects.media.view');

                    if ($maxWidth === null && $maxHeight === null) {
                        throw new QUI\Exception('At least one target dimension must be provided.');
                    }

                    $path = $Item->createSizeCache($maxWidth ?? false, $maxHeight ?? false);

                    if ($path === false) {
                        throw new QUI\Exception('The image variant could not be created. Is the media item active?');
                    }

                    $dimensions = $Item->getResizeSize($maxWidth ?? false, $maxHeight ?? false);

                    return [
                        'created' => true,
                        'image' => self::parseMediaItem($Item),
                        'variant' => [
                            'url' => $Item->getSizeCacheUrl($maxWidth ?? false, $maxHeight ?? false),
                            'width' => (int)$dimensions['width'],
                            'height' => (int)$dimensions['height'],
                            'mimeType' => (string)$Item->getAttribute('mime_type'),
                            'sizeBytes' => filesize($path) ?: 0
                        ]
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_image_variant_create',
            description: 'Creates and returns a cached image variant within the requested maximum dimensions.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'maxWidth' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 4000],
                    'maxHeight' => ['type' => ['integer', 'null'], 'minimum' => 1, 'maximum' => 4000]
                ],
                'anyOf' => [
                    ['required' => ['maxWidth']],
                    ['required' => ['maxHeight']]
                ]
            ]
        );
    }
}
