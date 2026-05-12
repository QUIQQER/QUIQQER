<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\CopySiteToLanguage
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class CopySiteToLanguage extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                string $sourceLang,
                int $sourceId,
                string $targetLang,
                int $targetParentId,
                bool | null $createLanguageLink = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $SourceSite = self::getEditSite($project, $sourceId, $sourceLang);
                    $TargetProject = self::getProject($project, $targetLang);
                    $CopiedSite = $SourceSite->copy($targetParentId, $TargetProject);
                    $languageLinked = false;

                    if ($createLanguageLink !== false) {
                        $SourceSite->addLanguageLink($targetLang, $CopiedSite->getId());
                        $languageLinked = true;
                    }

                    return [
                        'copied' => true,
                        'languageLinked' => $languageLinked,
                        'sourceSite' => self::parseSite($SourceSite, true),
                        'site' => self::parseSite($CopiedSite, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_copy_to_language',
            description: 'Copies one QUIQQER site to another language within the same project and can link the language variants.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'sourceLang', 'sourceId', 'targetLang', 'targetParentId'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'sourceLang' => ['type' => 'string', 'description' => 'Source project language.'],
                    'sourceId' => ['type' => 'integer', 'description' => 'Source site ID.', 'minimum' => 1],
                    'targetLang' => ['type' => 'string', 'description' => 'Target project language.'],
                    'targetParentId' => ['type' => 'integer', 'description' => 'Target parent site ID.', 'minimum' => 1],
                    'createLanguageLink' => ['type' => 'boolean', 'default' => true]
                ]
            ]
        );
    }
}
