<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Folder;
use Throwable;

class RestoreMedia extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $ids, int $parentId): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    $ids = self::validateIds($ids);
                    $Media = self::getProject($project)->getMedia();
                    $Parent = $Media->get($parentId);

                    if (!$Parent instanceof Folder || $Parent->isDeleted()) {
                        throw new QUI\Exception('The restore target is not an active media folder.');
                    }

                    foreach ($ids as $id) {
                        if (!$Media->get($id)->isDeleted()) {
                            throw new QUI\Exception('Media item ' . $id . ' is not in the trash.');
                        }
                    }

                    $restored = [];

                    foreach ($ids as $id) {
                        $Item = $Media->getTrash()->restore($id, $Parent);
                        $restored[] = [
                            'previousId' => $id,
                            'item' => self::parseMediaItem($Item, true)
                        ];
                    }

                    return [
                        'restored' => count($restored),
                        'parentId' => $parentId,
                        'items' => $restored
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_restore',
            description: 'Restores deleted media files into a selected folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'parentId'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getIdListSchema(),
                    'parentId' => ['type' => 'integer', 'minimum' => 1]
                ]
            ]
        );
    }
}
