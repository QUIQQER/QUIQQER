<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CreateSiteCache extends AbstractSiteAdministrationTool
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
                    $Site->createCache();

                    return [
                        'created' => true,
                        'site' => self::parseSite($Site)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_cache_create',
            description: 'Creates the object and rewritten URL caches of one QUIQQER site.',
            inputSchema: self::getSiteIdSchema()
        );
    }
}
