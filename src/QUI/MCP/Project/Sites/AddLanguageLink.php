<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\AddLanguageLink
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class AddLanguageLink extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $sourceId,
                string $targetLang,
                int $targetId,
                string | null $sourceLang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $SourceSite = self::getEditSite($project, $sourceId, $sourceLang);
                    $TargetSite = self::getEditSite($project, $targetId, $targetLang);

                    $SourceSite->addLanguageLink($targetLang, $TargetSite->getId());

                    return [
                        'linked' => true,
                        'sourceSite' => self::parseSite($SourceSite, true),
                        'targetSite' => self::parseSite($TargetSite, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_add_language_link',
            description: 'Links one QUIQQER site to another site in a different language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'sourceId', 'targetLang', 'targetId'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'sourceId' => ['type' => 'integer', 'description' => 'Source site ID.', 'minimum' => 1],
                    'sourceLang' => ['type' => 'string', 'description' => 'Source project language.'],
                    'targetLang' => ['type' => 'string', 'description' => 'Target project language.'],
                    'targetId' => ['type' => 'integer', 'description' => 'Target site ID.', 'minimum' => 1]
                ]
            ]
        );
    }
}
