<?php

/**
 * This file contains the \QUI\MCP\Project\ListProjects
 */

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Project;
use Throwable;

class ListProjects extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    return array_map(
                        static fn(Project $Project): array => self::parseProject($Project),
                        QUI::getProjectManager()->getProjects(true)
                    );
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_list',
            description: 'Lists all QUIQQER projects with their languages.'
        );
    }
}
