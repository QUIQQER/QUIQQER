<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class CreateDefaultStructure extends AbstractProjectLifecycleTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, ?string $lang = null): CallToolResult | array {
                try {
                    self::checkProjectAdministration();
                    $Project = self::getProject($project, $lang);
                    QUI\Utils\Project::createDefaultStructure($Project);

                    return [
                        'created' => true,
                        'project' => self::parseProject($Project)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_create_default_structure',
            description: 'Creates the idempotent QUIQQER default page structure for every language of one project.',
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
