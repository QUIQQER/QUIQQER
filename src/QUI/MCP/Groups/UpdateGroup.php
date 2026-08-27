<?php

namespace QUI\MCP\Groups;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\ToolHelper;
use Throwable;

class UpdateGroup extends AbstractGroupTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                int | string $group,
                array $attributes,
                int | string | null $parent = null
            ): CallToolResult | array {
                try {
                    self::checkGroupPermission('quiqqer.admin.groups.edit');

                    $Group = self::getGroup($group);
                    $filtered = self::filterGroupAttributes($attributes);

                    foreach ($filtered['attributes'] as $attribute => $value) {
                        $Group->setAttribute($attribute, $value);
                    }

                    $Group->save();

                    if ($parent !== null) {
                        self::getGroup($parent);
                        $Group->setParent($parent);
                    }

                    return [
                        'saved' => true,
                        'changedAttributes' => array_keys($filtered['attributes']),
                        'ignoredAttributes' => $filtered['ignored'],
                        'parentChanged' => $parent !== null,
                        'group' => self::parseGroup($Group)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_groups_update',
            description: 'Updates whitelisted attributes and optionally the parent of one group.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['group', 'attributes'],
                'properties' => [
                    'group' => self::getGroupIdSchema(),
                    'attributes' => [
                        'type' => 'object',
                        'description' => 'Whitelisted group attributes.',
                        'additionalProperties' => true
                    ],
                    'parent' => [
                        'description' => 'Optional new parent group UUID or legacy numeric ID.',
                        'oneOf' => [
                            ['type' => 'string', 'minLength' => 1],
                            ['type' => 'integer', 'minimum' => 0],
                            ['type' => 'null']
                        ]
                    ]
                ]
            ]
        );
    }
}
