<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class ListSiteTypes extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $result = [];

                    foreach (QUI::getPackageManager()->getAvailableSiteTypes() as $package => $entries) {
                        if (isset($entries['type'])) {
                            $entries = [$entries];
                        }

                        foreach ($entries as $entry) {
                            if (!is_array($entry) || empty($entry['type'])) {
                                continue;
                            }

                            $type = (string)$entry['type'];
                            $result[] = [
                                'type' => $type,
                                'package' => $package,
                                'title' => $entry['text'] ?? QUI::getPackageManager()->getSiteTypeName($type),
                                'icon' => $entry['icon'] ?? '',
                                'childrenType' => $entry['childrenType'] ?? null,
                                'childrenNavHide' => $entry['childrenNavHide'] ?? null
                            ];
                        }
                    }

                    return ['types' => $result];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_types_list',
            description: 'Lists installed QUIQQER site types with titles, icons and child defaults.'
        );
    }
}
