<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Interfaces\Projects\Media\File as MediaFile;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Item as MediaItem;
use Throwable;

class UpdateMediaOrder extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $folderId, array $orderedIds): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Folder = self::getMediaFolder($project, $folderId);
                    self::checkMediaPermission($Folder, 'quiqqer.projects.media.edit');
                    $orderedIds = self::validateMediaIds($orderedIds);
                    $items = self::applyMediaOrder($Folder, $orderedIds);

                    return [
                        'updated' => true,
                        'folder' => self::parseMediaItem($Folder),
                        'orderedIds' => array_map(
                            static fn($Item): int => $Item->getId(),
                            $items
                        ),
                        'items' => array_map(
                            static fn($Item): array => self::parseMediaItem($Item),
                            $items
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_order_update',
            description: 'Moves selected direct folder children to the front and persists a complete priority order.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'folderId', 'orderedIds'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'folderId' => ['type' => 'integer', 'minimum' => 1],
                    'orderedIds' => self::getMediaIdsSchema()
                ]
            ]
        );
    }

    /**
     * @param array<int, int> $orderedIds
     * @return array<int, MediaItem&MediaFile>
     */
    public static function applyMediaOrder(Folder $Folder, array $orderedIds): array
    {
        $Media = $Folder->getMedia();
        $existingIds = $Folder->getChildrenIds(['order' => 'priority']);

        if (!is_array($existingIds)) {
            $existingIds = [];
        }

        foreach ($orderedIds as $id) {
            if (!in_array($id, $existingIds, true)) {
                throw new QUI\Exception('Media item ' . $id . ' is not a direct child of the selected folder.');
            }
        }

        $completeOrder = array_merge(
            $orderedIds,
            array_values(array_diff($existingIds, $orderedIds))
        );
        $items = [];

        foreach ($completeOrder as $index => $id) {
            $Item = $Media->get($id);

            if (!$Item instanceof MediaItem) {
                throw new QUI\Exception('Media item ' . $id . ' is not manageable.');
            }

            $Item->setAttribute('priority', $index + 1);
            $Item->save(Server::getRequestUser());
            $items[] = $Item;
        }

        $Folder->setAttribute('order', 'priority');
        $Folder->save(Server::getRequestUser());

        return $items;
    }
}
