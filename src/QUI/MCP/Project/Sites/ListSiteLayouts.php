<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ListSiteLayouts extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, ?string $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Project = self::getProject($project, $lang);

                    return [
                        'project' => self::parseProject($Project),
                        'layouts' => $Project->getLayouts()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_layouts_list',
            description: 'Lists layouts available to sites in one QUIQQER project.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'lang' => ['type' => ['string', 'null'], 'pattern' => '^[a-z]{2}$']
                ]
            ]
        );
    }
}
