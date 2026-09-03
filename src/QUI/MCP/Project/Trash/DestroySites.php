<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Site\Edit;
use Throwable;

class DestroySites extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                array $ids,
                bool $confirm,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    self::requireConfirmation($confirm);
                    $ids = self::validateIds($ids);
                    $Project = self::getProject($project, $lang);

                    foreach ($ids as $id) {
                        $Site = new Edit($Project, $id);

                        if ((int)$Site->getAttribute('deleted') !== 1) {
                            throw new QUI\Exception('Site ' . $id . ' is not in the trash.');
                        }
                    }

                    $Project->getTrash()->destroy($ids);

                    return [
                        'destroyed' => count($ids),
                        'ids' => $ids,
                        'permanent' => true
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_destroy',
            description: 'Permanently destroys deleted sites. This operation cannot be undone.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'confirm'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getIdListSchema(),
                    'confirm' => self::getConfirmationSchema(),
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
