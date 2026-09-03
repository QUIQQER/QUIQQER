<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class GetProject extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, ?string $lang = null): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    return [
                        'project' => self::parseProject(self::getProject($project, $lang))
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_get',
            description: 'Returns one QUIQQER project and its current language context.',
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
