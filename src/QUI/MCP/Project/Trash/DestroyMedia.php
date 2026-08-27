<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class DestroyMedia extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, array $ids, bool $confirm): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    self::requireConfirmation($confirm);
                    $ids = self::validateIds($ids);
                    $Media = self::getProject($project)->getMedia();

                    foreach ($ids as $id) {
                        if (!$Media->get($id)->isDeleted()) {
                            throw new QUI\Exception('Media item ' . $id . ' is not in the trash.');
                        }
                    }

                    foreach ($ids as $id) {
                        $Media->getTrash()->destroy($id);
                    }

                    return [
                        'destroyed' => count($ids),
                        'ids' => $ids,
                        'permanent' => true
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_destroy',
            description: 'Permanently destroys deleted media files. This operation cannot be undone.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'confirm'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getIdListSchema(),
                    'confirm' => self::getConfirmationSchema()
                ]
            ]
        );
    }
}
