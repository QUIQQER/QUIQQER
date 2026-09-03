<?php

namespace QUI\MCP\Permissions;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Site\Edit;
use Throwable;

class UpdateSitePermissions extends AbstractPermissionTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                array $permissions,
                string | null $lang = null,
                bool $recursive = false
            ): CallToolResult | array {
                try {
                    self::checkPermissionAdministration();
                    $Site = self::getManagedSite($project, $id, $lang);
                    $Project = $Site->getProject();
                    $Manager = QUI::getPermissionManager();
                    $permissions = self::validatePermissionValues($permissions, 'site');
                    $target = [
                        'type' => 'site',
                        'project' => $Project->getName(),
                        'lang' => $Project->getLang(),
                        'id' => $Site->getId()
                    ];

                    $Manager->setPermissions($Site, $permissions, Server::getRequestUser());

                    $updatedChildren = [];
                    $errors = [];

                    if ($recursive) {
                        foreach ($Site->getChildrenIdsRecursive(['active' => '0&1']) as $childId) {
                            try {
                                $Child = new Edit($Project, (int)$childId);
                                $Manager->setPermissions($Child, $permissions, Server::getRequestUser());
                                $updatedChildren[] = $Child->getId();
                            } catch (Throwable $Exception) {
                                $errors[] = [
                                    'id' => (int)$childId,
                                    'message' => $Exception->getMessage()
                                ];
                            }
                        }
                    }

                    return [
                        'updated' => array_keys($permissions),
                        'recursive' => $recursive,
                        'updatedChildren' => $updatedChildren,
                        'errors' => $errors,
                        'result' => self::getPermissionsResponse($Site, $target)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_permissions_site_update',
            description: 'Partially updates site permissions, optionally including every descendant.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'permissions'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'permissions' => self::getPermissionsSchema(),
                    'lang' => ['type' => ['string', 'null']],
                    'recursive' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }
}
