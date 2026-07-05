<?php

/**
 * This file contains the \QUI\MCP\Project\Media\ActivateMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ActivateMedia extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Item = self::getProject($project, $lang)->getMedia()->get($id);
                    $Item->activate(Server::getRequestUser());

                    return [
                        'activated' => true,
                        'item' => self::parseMediaItem($Item, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_activate',
            description: 'Activates one media item.',
            inputSchema: self::mediaIdSchema()
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected static function mediaIdSchema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['project', 'id'],
            'properties' => [
                'project' => ['type' => 'string', 'description' => 'Project name.'],
                'id' => ['type' => 'integer', 'description' => 'Media item ID.', 'minimum' => 1],
                'lang' => ['type' => 'string', 'description' => 'Project language.']
            ]
        ];
    }
}
