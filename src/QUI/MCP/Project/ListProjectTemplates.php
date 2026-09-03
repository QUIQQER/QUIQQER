<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ListProjectTemplates extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $templates = [];

                    foreach (
                        QUI::getPackageManager()->searchInstalledPackages([
                            'type' => 'quiqqer-template'
                        ]) as $package
                    ) {
                        $templates[] = [
                            'name' => $package['name'] ?? null,
                            'title' => $package['title'] ?? ($package['name'] ?? null),
                            'description' => $package['description'] ?? null,
                            'version' => $package['version'] ?? null
                        ];
                    }

                    return ['templates' => $templates];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_templates_list',
            description: 'Lists installed QUIQQER template packages available for project creation.'
        );
    }
}
