<?php

namespace QUI\MCP\Project;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ListAvailableLanguages extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $languages = array_values(array_unique(array_map(
                        static fn(mixed $language): string => strtolower(trim((string)$language)),
                        QUI::availableLanguages()
                    )));
                    sort($languages);

                    return ['languages' => $languages];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_projects_languages_list',
            description: 'Lists installed language codes that can be used for QUIQQER projects.'
        );
    }
}
