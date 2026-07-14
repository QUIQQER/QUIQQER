<?php

/**
 * This file contains the \QUI\MCP\Project\Media\FinalizeUpload
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\AI\MCP\Upload\DirectUploadService;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Folder;
use Throwable;

class FinalizeUpload extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $uploadId): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $service = new DirectUploadService();
                    $session = $service->getOwnedSession($uploadId);
                    $metadata = $session['metadata'] ?? [];

                    if (!is_array($metadata) || ($metadata['type'] ?? null) !== 'quiqqer.media') {
                        throw new QUI\Exception('Upload session is not a media upload session.');
                    }

                    if ((string)($session['status'] ?? '') !== DirectUploadService::STATUS_UPLOADED) {
                        throw new QUI\Exception('Upload session has no uploaded file.');
                    }

                    $filePath = (string)($session['filePath'] ?? '');

                    if ($filePath === '' || !is_file($filePath)) {
                        throw new QUI\Exception('Uploaded file is missing.');
                    }

                    $Parent = self::getProject(
                        (string)$metadata['project'],
                        is_string($metadata['lang'] ?? null) ? $metadata['lang'] : null
                    )->getMedia()->get((int)$metadata['parentId']);

                    if (!$Parent instanceof Folder) {
                        throw new QUI\Exception('Media item is not a folder.');
                    }

                    $File = $Parent->uploadFile(
                        $filePath,
                        self::parseOverwrite((string)($metadata['overwrite'] ?? 'none')),
                        \QUI\AI\MCP\Server::getRequestUser()
                    );

                    $service->deleteSession($uploadId);

                    return [
                        'uploaded' => true,
                        'file' => self::parseMediaItem($File, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_finalize_upload',
            description: 'Imports a direct HTTP upload session into the QUIQQER media folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['uploadId'],
                'properties' => [
                    'uploadId' => ['type' => 'string', 'description' => 'Upload session ID.']
                ]
            ]
        );
    }

    protected static function parseOverwrite(string $overwrite): int
    {
        return match ($overwrite) {
            'overwrite' => Folder::FILE_OVERWRITE_TRUE,
            'destroy' => Folder::FILE_OVERWRITE_DESTROY,
            default => Folder::FILE_OVERWRITE_NONE,
        };
    }
}
