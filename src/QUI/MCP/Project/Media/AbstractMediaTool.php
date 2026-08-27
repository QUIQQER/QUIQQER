<?php

namespace QUI\MCP\Project\Media;

use QUI;
use QUI\AI\MCP\Server;
use QUI\Interfaces\Projects\Media\File as MediaFile;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media;
use QUI\Projects\Media\Folder;
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
