<?php

/**
 * This file contains the \QUI\MCP\Project\Sites\CreateChild
 */

namespace QUI\MCP\Project\Sites;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use Throwable;

class CreateChild extends AbstractTool
{
    protected const ATTRIBUTES = [
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
        'image_emotion',
        'image_site'
    ];

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $parentId,
                array $attributes,
                string | null $lang = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Project = self::getProject($project, $lang);
                    $Parent = self::getEditSite($project, $parentId, $lang);
                    $createParams = [];

                    foreach (['name', 'title', 'short', 'content'] as $attribute) {
                        if (isset($attributes[$attribute]) && is_scalar($attributes[$attribute])) {
                            $createParams[$attribute] = $attributes[$attribute];
                        }
                    }

                    $childId = $Parent->createChild($createParams, [], Server::getRequestUser());
                    $Child = self::getEditSite($project, $childId, $lang);
                    $changed = [];

                    foreach ($attributes as $attribute => $value) {
                        if (!in_array($attribute, self::ATTRIBUTES, true)) {
                            continue;
                        }

                        if ($attribute === 'active') {
                            continue;
                        }

                        if (!is_scalar($value) && $value !== null) {
                            continue;
                        }

                        $Child->setAttribute($attribute, $value);
                        $changed[$attribute] = $value;
                    }

                    if (!empty($changed)) {
                        $Child->save(Server::getRequestUser());
                    }

                    if (isset($attributes['active'])) {
                        if ((bool)$attributes['active']) {
                            $Child->activate(Server::getRequestUser());
                        } else {
                            $Child->deactivate(Server::getRequestUser());
                        }
                    }

                    return [
                        'created' => true,
                        'parent' => self::parseSite($Parent),
                        'site' => self::parseSite($Project->get($childId), true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_sites_create_child',
            description: 'Creates a child site below a parent site.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'parentId', 'attributes'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'description' => 'Parent site ID.', 'minimum' => 1],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Initial site attributes.',
                        'additionalProperties' => true
                    ]
                ]
            ]
        );
    }
}
