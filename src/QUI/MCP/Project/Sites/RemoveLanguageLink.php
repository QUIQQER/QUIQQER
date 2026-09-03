<?php

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class RemoveLanguageLink extends AbstractSiteAdministrationTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                string $targetLang,
                ?string $lang = null
            ): CallToolResult | array {
                try {
                    $Site = self::getManagedSite(
                        $project,
                        $id,
                        $lang,
                        'quiqqer.projects.site.edit'
                    );
                    $targetLang = strtolower(trim($targetLang));

                    if (!in_array($targetLang, $Site->getProject()->getLanguages(), true)) {
                        throw new QUI\Exception('The target language is not part of this project.');
                    }

                    if ($targetLang === $Site->getProject()->getLang()) {
                        throw new QUI\Exception('The current site language cannot be removed as a language link.');
                    }

                    $affectedRows = $Site->removeLanguageLink($targetLang);

                    return [
                        'removed' => $affectedRows > 0,
                        'affectedRows' => $affectedRows,
                        'targetLang' => $targetLang,
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_remove_language_link',
            description: 'Removes one multilingual link from a QUIQQER site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'targetLang'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'targetLang' => ['type' => 'string', 'pattern' => '^[a-z]{2}$'],
                    'lang' => ['type' => ['string', 'null'], 'pattern' => '^[a-z]{2}$']
                ]
            ]
        );
    }
}
