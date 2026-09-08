<?php

/**
 * This file contains the \QUI\MCP\Project\Media\CreateFolder
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Folder;
use Throwable;

class CreateFolder extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $parentId,
                string $name,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Parent = self::getProject($project, $lang)->getMedia()->get($parentId);

                    if (!$Parent instanceof Folder) {
                        throw new \QUI\Exception('Media item is not a folder.');
                    }

                    $Folder = $Parent->createFolder($name, Server::getRequestUser());

                    return [
                        'created' => true,
                        'folder' => self::parseMediaItem($Folder, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_create_folder',
            description: 'Creates a media folder below another media folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'parentId', 'name'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'description' => 'Parent media folder ID.', 'minimum' => 1],
                    'name' => ['type' => 'string', 'description' => 'Folder name.'],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }
}
