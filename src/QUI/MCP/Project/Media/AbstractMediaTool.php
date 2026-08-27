<?php

namespace QUI\MCP\Project\Media;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Projects\Media\File as MediaFile;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media;
use QUI\Projects\Media\Folder;
use QUI\Projects\Media\Image;
use QUI\Projects\Media\Item as MediaItem;

abstract class AbstractMediaTool extends AbstractTool
{
    protected const MAX_DOWNLOAD_BYTES = 5_242_880;
    protected const MAX_REPLACE_BYTES = 52_428_800;

    protected static function getMedia(string $project): Media
    {
        return self::getProject($project)->getMedia();
    }

    protected static function getMediaItem(string $project, int $id): MediaItem & MediaFile
    {
        $Item = self::getMedia($project)->get($id);

        if (!$Item instanceof MediaItem) {
            throw new QUI\Exception('Media item ' . $id . ' is not manageable.');
        }

        return $Item;
    }

    protected static function getMediaFolder(string $project, int $id): Folder
    {
        $Item = self::getMediaItem($project, $id);

        if (!$Item instanceof Folder) {
            throw new QUI\Exception('Media item ' . $id . ' is not a folder.');
        }

        return $Item;
    }

    protected static function getMediaEffectItem(string $project, int $id): Folder | Image
    {
        $Item = self::getMediaItem($project, $id);

        if (!$Item instanceof Folder && !$Item instanceof Image) {
            throw new QUI\Exception('Media item ' . $id . ' does not support image effects.');
        }

        return $Item;
    }

    /**
     * @param array<array-key, mixed> $ids
     * @return array<int, int>
     */
    protected static function validateMediaIds(array $ids): array
    {
        if ($ids === []) {
            throw new QUI\Exception('At least one media ID must be provided.');
        }

        $result = [];

        foreach ($ids as $id) {
            if (!is_int($id) || $id < 1) {
                throw new QUI\Exception('Every media ID must be a positive integer.');
            }

            $result[$id] = $id;
        }

        return array_values($result);
    }

    /**
     * @param array<array-key, mixed> $effects
     * @return array<string, int|string|null>
     */
    protected static function normalizeMediaEffectUpdates(array $effects): array
    {
        if ($effects === []) {
            throw new QUI\Exception('At least one image effect must be provided.');
        }

        $normalized = [];

        foreach ($effects as $effect => $value) {
            if (!is_string($effect)) {
                throw new QUI\Exception('Every image effect name must be a string.');
            }

            switch ($effect) {
                case 'blur':
                    $normalized[$effect] = self::normalizeEffectInteger($effect, $value, 0, 100);
                    break;

                case 'brightness':
                case 'contrast':
                    $normalized[$effect] = self::normalizeEffectInteger($effect, $value, -100, 100);
                    break;

                case 'greyscale':
                    if ($value === null) {
                        $normalized[$effect] = null;
                        break;
                    }

                    if (!is_bool($value) && $value !== 0 && $value !== 1) {
                        throw new QUI\Exception('Image effect "greyscale" must be a boolean.');
                    }

                    $normalized[$effect] = $value ? 1 : 0;
                    break;

                case 'watermark':
                    if (
                        $value !== null
                        && !is_int($value)
                        && $value !== ''
                        && $value !== 'default'
                    ) {
                        throw new QUI\Exception(
                            'Image effect "watermark" must be a media image ID, "default", an empty string or null.'
                        );
                    }

                    if (is_int($value) && $value < 1) {
                        throw new QUI\Exception('A watermark media image ID must be positive.');
                    }

                    $normalized[$effect] = $value;
                    break;

                case 'watermark_position':
                    if ($value === null) {
                        $normalized[$effect] = null;
                        break;
                    }

                    if (
                        !is_string($value)
                        || !in_array($value, [
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
                        ], true)
                    ) {
                        throw new QUI\Exception('Unknown watermark position.');
                    }

                    $normalized[$effect] = $value;
                    break;

                case 'watermark_ratio':
                    $normalized[$effect] = self::normalizeEffectInteger($effect, $value, 1, 100);
                    break;

                default:
                    throw new QUI\Exception('Unknown image effect: ' . $effect);
            }
        }

        return $normalized;
    }

    protected static function normalizeEffectInteger(
        string $effect,
        mixed $value,
        int $minimum,
        int $maximum
    ): ?int {
        if ($value === null) {
            return null;
        }

        if (!is_int($value) || $value < $minimum || $value > $maximum) {
            throw new QUI\Exception(
                'Image effect "' . $effect . '" must be an integer between '
                . $minimum . ' and ' . $maximum . ' or null.'
            );
        }

        return $value;
    }

    protected static function checkMediaPermission(MediaItem & MediaFile $Item, string $permission): void
    {
        $Item->checkPermission($permission, Server::getRequestUser());
    }

    protected static function validateMoveTarget(MediaItem & MediaFile $Item, Folder $Target): void
    {
        if (!$Item instanceof Folder) {
            return;
        }

        if ($Item->getId() === $Target->getId() || in_array($Item->getId(), $Target->getParentIds(), true)) {
            throw new QUI\Exception('A folder cannot be moved or copied into itself or one of its descendants.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getMediaIdsSchema(): array
    {
        return [
            'type' => 'array',
            'minItems' => 1,
            'uniqueItems' => true,
            'items' => ['type' => 'integer', 'minimum' => 1]
        ];
    }

    protected static function getDownloadLimit(?int $maxBytes): int
    {
        return min(self::MAX_DOWNLOAD_BYTES, max(1, $maxBytes ?? self::MAX_DOWNLOAD_BYTES));
    }

    /**
     * @return array<string, mixed>
     */
    protected static function readDownload(string $path, string $filename, string $mimeType, int $maxBytes): array
    {
        if (!is_file($path)) {
            throw new QUI\Exception('Download file does not exist.', 404);
        }

        $size = filesize($path);

        if ($size === false) {
            throw new QUI\Exception('Could not determine download size.');
        }

        if ($size > $maxBytes) {
            throw new QUI\Exception(
                'Download exceeds the MCP limit of ' . $maxBytes . ' bytes. File size: ' . $size . ' bytes.'
            );
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new QUI\Exception('Could not read download file.');
        }

        return [
            'filename' => $filename,
            'mimeType' => $mimeType,
            'size' => $size,
            'encoding' => 'base64',
            'contentBase64' => base64_encode($content)
        ];
    }
}
