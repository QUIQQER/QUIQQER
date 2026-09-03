<?php

namespace QUI\MCP\Project\Trash;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Site\Edit;
use Throwable;

class RestoreSites extends AbstractTrashTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                array $ids,
                int $parentId,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkTrashPermission();
                    $ids = self::validateIds($ids);
                    $Project = self::getProject($project, $lang);
                    $Parent = new Edit($Project, $parentId);

                    if ((int)$Parent->getAttribute('deleted') === 1) {
                        throw new QUI\Exception('The restore target site is deleted.');
                    }

                    foreach ($ids as $id) {
                        $Site = new Edit($Project, $id);

                        if ((int)$Site->getAttribute('deleted') !== 1) {
                            throw new QUI\Exception('Site ' . $id . ' is not in the trash.');
                        }
                    }

                    $Project->getTrash()->restore($Project, $ids, $parentId);

                    return [
                        'restored' => count($ids),
                        'parentId' => $parentId,
                        'sites' => array_map(
                            static fn(int $id): array => self::parseSite(new Edit($Project, $id), true),
                            $ids
                        )
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_restore',
            description: 'Restores deleted sites below a selected parent and leaves them inactive.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'ids', 'parentId'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'ids' => self::getIdListSchema(),
                    'parentId' => ['type' => 'integer', 'minimum' => 1],
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
