<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Lock\Locker;
use Throwable;

class LockSite extends AbstractSiteAdministrationTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, int $id, ?string $lang = null): CallToolResult | array {
                try {
                    $Site = self::getManagedSite(
                        $project,
                        $id,
                        $lang,
                        'quiqqer.projects.site.edit'
                    );
                    $owner = self::getLockOwner($Site);
                    $requestUserId = (string)Server::getRequestUser()->getUUID();
                    $acquired = false;

                    if ($owner === false) {
                        Locker::lock(
                            QUI::getPackage('quiqqer/core'),
                            self::getLockKey($Site),
                            false,
                            Server::getRequestUser()
                        );
                        $acquired = true;
                    } elseif ((string)$owner !== $requestUserId) {
                        throw new QUI\Exception('The site is already locked by another user.');
                    }

                    return [
                        'acquired' => $acquired,
                        'lock' => self::getLockResponse($Site, $acquired ? $requestUserId : $owner)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_lock',
            description: 'Acquires an editing lock for one site on behalf of the MCP request user.',
            inputSchema: self::getSiteIdSchema()
        );
    }
}
