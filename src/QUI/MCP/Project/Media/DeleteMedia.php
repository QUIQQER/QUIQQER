<?php

/**
 * This file contains the \QUI\MCP\Project\Media\DeleteMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class DeleteMedia extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, string | null $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Item = self::getProject($project, $lang)->getMedia()->get($id);
                    $Item->delete(Server::getRequestUser());

                    return [
                        'deleted' => true,
                        'id' => $id,
                        'project' => $project,
                        'lang' => $lang
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_delete',
            description: 'Deletes one media item and moves it to the media trash.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Media item ID.', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
