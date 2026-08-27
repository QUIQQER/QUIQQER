<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetMediaEffects extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Item = self::getMediaEffectItem($project, $id);
                    self::checkMediaPermission($Item, 'quiqqer.projects.media.view');

                    return [
                        'item' => self::parseMediaItem($Item),
                        'effects' => $Item->getEffects()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_effects_get',
            description: 'Returns the configured image effects of one media image or folder.',
            inputSchema: self::getMediaItemSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getMediaItemSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['project', 'id'],
            'properties' => [
                'project' => ['type' => 'string', 'minLength' => 1],
                'id' => ['type' => 'integer', 'minimum' => 1]
            ]
        ];
    }
}
