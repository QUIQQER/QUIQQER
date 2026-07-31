<?php

/**
 * This file contains the \QUI\MCP\Project\AddLanguage
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Manager;
use Throwable;

use function in_array;
use function strtolower;
use function trim;

class AddLanguage extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $project, string $lang): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project);
                    $lang = strtolower(trim($lang));
                    $alreadyExists = in_array($lang, $Project->getLanguages(), true);
                    $Project = Manager::addLanguage($project, $lang);

                    return [
                        'added' => !$alreadyExists,
                        'alreadyExists' => $alreadyExists,
                        'project' => self::parseProject($Project)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_add_language',
            description: 'Adds an installed two-letter language to a QUIQQER project and runs project setup.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'lang'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'lang' => [
                        'type' => 'string',
                        'description' => 'Installed two-letter language code.',
                        'pattern' => '^[a-z]{2}$'
                    ]
                ]
            ]
        );
    }
}
