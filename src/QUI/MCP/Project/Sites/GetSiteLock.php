<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class GetSiteLock extends AbstractSiteAdministrationTool
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
                        'quiqqer.projects.site.view'
                    );

                    return self::getLockResponse($Site);
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_lock_get',
            description: 'Returns the current editing lock and owner of one QUIQQER site.',
            inputSchema: self::getSiteIdSchema()
        );
    }
}
