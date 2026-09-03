<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Image;
use Throwable;

class UpdateMediaEffects extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                array $effects,
                bool $recursive = false
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    $Item = self::getMediaEffectItem($project, $id);
                    self::checkMediaPermission($Item, 'quiqqer.projects.media.edit');

                    if ($recursive && !$Item instanceof Folder) {
                        throw new QUI\Exception('Recursive image effects require a media folder.');
                    }

                    $updates = self::normalizeMediaEffectUpdates($effects);
                    $normalizedUpdates = self::resolveWatermark($project, $updates);
                    $mergedEffects = self::mergeEffects($Item->getEffects(), $normalizedUpdates);
                    $Item->setEffects($mergedEffects);
                    $Item->save(Server::getRequestUser());

                    $updatedChildren = [];
                    $errors = [];

                    if ($recursive) {
                        self::applyEffectsRecursive(
                            $Item,
                            $mergedEffects,
                            $updatedChildren,
                            $errors
                        );
                    }

                    return [
                        'updated' => true,
                        'recursive' => $recursive,
                        'updatedChildren' => $updatedChildren,
                        'errors' => $errors,
                        'item' => self::parseMediaItem($Item),
                        'effects' => $Item->getEffects()
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_effects_update',
            description: 'Partially updates image effects of an image or folder and can apply them recursively.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'effects'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'effects' => self::getEffectsSchema(),
                    'recursive' => ['type' => 'boolean', 'default' => false]
                ]
            ]
        );
    }

    /**
     * @param array<string, mixed> $effects
     * @param array<int, int> $updatedChildren
     * @param array<int, array{id: int, message: string}> $errors
     */
    private static function applyEffectsRecursive(
        Folder $Folder,
        array $effects,
        array &$updatedChildren,
        array &$errors
    ): void {
        foreach ($Folder->getChildren() as $Child) {
            if (!$Child instanceof Folder && !$Child instanceof Image) {
                continue;
            }

            try {
                $Child->setEffects($effects);
                $Child->save(Server::getRequestUser());
                $updatedChildren[] = $Child->getId();
            } catch (Throwable $Exception) {
                $errors[] = [
                    'id' => $Child->getId(),
                    'message' => $Exception->getMessage()
                ];

                continue;
            }

            if ($Child instanceof Folder) {
                self::applyEffectsRecursive($Child, $effects, $updatedChildren, $errors);
            }
        }
    }

    /**
     * @param array<string, int|string|null> $effects
     * @return array<string, int|string|null>
     */
    private static function resolveWatermark(string $project, array $effects): array
    {
        $watermark = $effects['watermark'] ?? null;

        if (!is_int($watermark)) {
            return $effects;
        }

        $Watermark = self::getMedia($project)->get($watermark);

        if (!$Watermark instanceof Image) {
            throw new QUI\Exception('The selected watermark media item is not an image.');
        }

        self::checkMediaPermission($Watermark, 'quiqqer.projects.media.view');
        $effects['watermark'] = $Watermark->getUrl();

        return $effects;
    }

    /**
     * @param array<string, mixed> $current
     * @param array<string, int|string|null> $updates
     * @return array<string, mixed>
     */
    private static function mergeEffects(array $current, array $updates): array
    {
        foreach ($updates as $effect => $value) {
            if ($value === null) {
                unset($current[$effect]);
                continue;
            }

            $current[$effect] = $value;
        }

        return $current;
    }

    /**
     * @return array<string, mixed>
     */
    private static function getEffectsSchema(): array
    {
        $nullableInteger = static fn(int $minimum, int $maximum): array => [
            'oneOf' => [
                ['type' => 'integer', 'minimum' => $minimum, 'maximum' => $maximum],
                ['type' => 'null']
            ]
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'minProperties' => 1,
            'properties' => [
                'blur' => $nullableInteger(0, 100),
                'brightness' => $nullableInteger(-100, 100),
                'contrast' => $nullableInteger(-100, 100),
                'greyscale' => [
                    'oneOf' => [
                        ['type' => 'boolean'],
                        ['type' => 'null']
                    ]
                ],
                'watermark' => [
                    'description' => 'Watermark image ID, "default", empty string to disable it, or null to inherit.',
                    'oneOf' => [
                        ['type' => 'integer', 'minimum' => 1],
                        ['type' => 'string', 'enum' => ['', 'default']],
                        ['type' => 'null']
                    ]
                ],
                'watermark_position' => [
                    'oneOf' => [
                        [
                            'type' => 'string',
                            'enum' => [
                                '',
                                'top-left',
                                'top',
                                'top-right',
                                'left',
                                'center',
                                'right',
                                'bottom-left',
                                'bottom',
                                'bottom-right'
                            ]
                        ],
                        ['type' => 'null']
                    ]
                ],
                'watermark_ratio' => $nullableInteger(1, 100)
            ]
        ];
    }
}
