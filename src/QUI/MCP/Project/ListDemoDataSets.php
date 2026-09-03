<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class ListDemoDataSets extends AbstractProjectLifecycleTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $template): CallToolResult | array {
                try {
                    self::checkProjectAdministration();
                    $template = self::validateTemplate($template);

                    return [
                        'template' => $template,
                        'sets' => QUI\Utils\Project::getDemoDataSetsForTemplate($template)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_demo_data_list',
            description: 'Lists demo data sets provided by one installed QUIQQER template.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['template'],
                'properties' => [
                    'template' => ['type' => 'string', 'minLength' => 1]
                ]
            ]
        );
    }
}
