<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Lock\Locker;
use QUI\Permissions\Permission;
use Throwable;

class UnlockSite extends AbstractSiteAdministrationTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                ?string $lang = null,
                bool $force = false
            ): CallToolResult | array {
                try {
                    $Site = self::getManagedSite(
                        $project,
                        $id,
                        $lang,
                        'quiqqer.projects.site.edit'
                    );
                    $owner = self::getLockOwner($Site);
                    $requestUserId = (string)Server::getRequestUser()->getUUID();
                    $released = false;

                    if ($owner !== false) {
                        if ((string)$owner !== $requestUserId) {
                            if (!$force) {
                                throw new QUI\Exception(
                                    'The site lock belongs to another user. Use force=true as an administrator.'
                                );
                            }

                            Permission::checkAdminUser(Server::getRequestUser());
                        }

                        Locker::unlock(
                            QUI::getPackage('quiqqer/core'),
                            self::getLockKey($Site)
                        );
                        $released = true;
                    }

                    return [
                        'released' => $released,
                        'forced' => $force,
                        'lock' => self::getLockResponse($Site, false)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_unlock',
            description: 'Releases an owned site lock; administrators may release another user lock with force=true.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'lang' => ['type' => ['string', 'null'], 'pattern' => '^[a-z]{2}$'],
                    'force' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }
}
