<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class LinkSite extends AbstractSiteAdministrationTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                int $parentId,
                ?string $lang = null
            ): CallToolResult | array {
                try {
                    $Site = self::getManagedSite(
                        $project,
                        $id,
                        $lang,
                        'quiqqer.projects.site.edit'
                    );
                    $Parent = self::getEditSite($project, $parentId, $lang);
                    $Parent->checkPermission('quiqqer.projects.site.new', Server::getRequestUser());
                    $Site->linked($Parent->getId());

                    return [
                        'linked' => true,
                        'parentId' => $Parent->getId(),
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_link',
            description: 'Adds another parent link for an existing QUIQQER site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'parentId'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'parentId' => ['type' => 'integer', 'minimum' => 1],
                    'lang' => ['type' => ['string', 'null'], 'pattern' => '^[a-z]{2}$']
                ]
            ]
        );
    }
}
