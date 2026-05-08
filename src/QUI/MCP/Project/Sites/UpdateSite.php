<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\UpdateSite
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Site\Edit;
use Throwable;

class UpdateSite extends AbstractTool
{
    protected const UPDATE_ATTRIBUTES = [
        'name',
        'title',
        'short',
        'content',
        'type',
        'active',
        'nav_hide',
        'hide',
        'release_from',
        'release_to',
        'meta_description',
        'meta_keywords',
        'quiqqer.site.template',
        'quiqqer.meta.site.robots',
        'quiqqer.meta.site.title',
        'quiqqer.meta.site.description',
        'quiqqer.meta.site.canonical',
        'image_emotion',
        'image_site'
    ];

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                array $attributes,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Site = new Edit(self::getProject($project, $lang), $id);
                    $allowedAttributes = self::getUpdateAttributes($Site);
                    $changed = [];

                    foreach ($attributes as $attribute => $value) {
                        if (!in_array($attribute, $allowedAttributes, true)) {
                            continue;
                        }

                        if (!is_scalar($value) && $value !== null) {
                            continue;
                        }

                        $Site->setAttribute($attribute, $value);
                        $changed[$attribute] = $value;
                    }

                    $Site->save(Server::getRequestUser());

                    return [
                        'saved' => true,
                        'changedAttributes' => array_keys($changed),
                        'site' => self::parseSite($Site, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_update',
            description: 'Updates whitelisted attributes of one QUIQQER site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'attributes'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'id' => ['type' => 'integer', 'description' => 'Site ID.', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Whitelisted attributes to update.',
                        'additionalProperties' => true
                    ]
                ]
            ]
        );
    }

    protected static function getUpdateAttributes(Edit $Site): array
    {
        /** @var array<int|string, mixed> $attributes */
        $attributes = self::UPDATE_ATTRIBUTES;

        QUI::getEvents()->fireEvent('mcpSiteUpdateAttributes', [&$attributes, $Site]);

        return array_values(array_unique(array_filter(
            $attributes,
            static function (mixed $attribute): bool {
                return is_string($attribute) && $attribute !== '';
            }
        )));
    }
}
