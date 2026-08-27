<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ClearSiteTrash extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                bool $confirm,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    self::requireConfirmation($confirm);
                    $Project = self::getProject($project, $lang);
                    $Trash = $Project->getTrash();
                    $before = $Trash->getList(['limit' => '0,1']);
                    $Trash->clear();

                    return [
                        'destroyed' => (int)($before['total'] ?? 0),
                        'cleared' => true,
                        'permanent' => true
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_trash_clear',
            description: 'Permanently destroys every deleted site in one project language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'confirm'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'confirm' => self::getConfirmationSchema(),
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
