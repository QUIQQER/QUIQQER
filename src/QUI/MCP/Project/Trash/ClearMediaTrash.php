<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ClearMediaTrash extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, bool $confirm): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    self::requireConfirmation($confirm);
                    $Trash = self::getProject($project)->getMedia()->getTrash();
                    $before = $Trash->getList(['limit' => '0,1']);
                    $Trash->clear();
                    $after = $Trash->getList(['limit' => '0,1']);
                    $remaining = (int)($after['total'] ?? 0);

                    return [
                        'destroyed' => max(0, (int)($before['total'] ?? 0) - $remaining),
                        'remaining' => $remaining,
                        'cleared' => $remaining === 0,
                        'permanent' => true
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_trash_clear',
            description: 'Permanently destroys every deleted media file in one project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'confirm'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'confirm' => self::getConfirmationSchema()
                ]
            ]
        );
    }
}
