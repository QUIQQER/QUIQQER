<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\CopySite
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class CopySite extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                int $targetParentId,
                string | null $lang = null,
                string | null $targetProject = null,
                string | null $targetLang = null,
                bool | null $createLanguageLink = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $SourceSite = self::getEditSite($project, $id, $lang);
                    $TargetProject = self::getProject(
                        $targetProject ?: $project,
                        $targetLang
                    );

                    $CopiedSite = $SourceSite->copy($targetParentId, $TargetProject);
                    $languageLinked = false;

                    if (
                        $createLanguageLink === true
                        && $TargetProject->getName() === $SourceSite->getProject()->getName()
                        && $TargetProject->getLang() !== $SourceSite->getProject()->getLang()
                    ) {
                        $SourceSite->addLanguageLink(
                            $TargetProject->getLang(),
                            $CopiedSite->getId()
                        );

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
            name: 'quiqqer_sites_copy',
            description: 'Copies one QUIQQER site below another parent site, optionally into another project language.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'targetParentId'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Source project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Source site ID.', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Source project language.'],
                    'targetParentId' => ['type' => 'integer', 'description' => 'Target parent site ID.', 'minimum' => 1],
                    'targetProject' => ['type' => 'string', 'description' => 'Target project name. Defaults to project.'],
                    'targetLang' => ['type' => 'string', 'description' => 'Target project language.'],
                    'createLanguageLink' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }
}
