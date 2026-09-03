<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetSitePermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Site = self::getManagedSite($project, $id, $lang);
                    $Project = $Site->getProject();

                    return self::getPermissionsResponse($Site, [
                        'type' => 'site',
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang(),
                        'id' => $Site->getId()
                    ]);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_site_get',
            description: 'Returns configured permission values of one site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'lang' => ['type' => ['string', 'null']]
                ]
            ]
        );
    }
}
