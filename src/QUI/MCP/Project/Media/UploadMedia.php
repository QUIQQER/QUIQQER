<?php

/**
 * This file contains the \QUI\MCP\Project\Media\UploadMedia
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Folder;
use Throwable;

class UploadMedia extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $parentId,
                string $filename,
                string $contentBase64,
                string | null $overwrite = null,
                string | null $lang = null
            ): CallToolResult | array {
                $tmpFile = null;

                try {
                    self::checkCorePermission();

                    $Parent = self::getProject($project, $lang)->getMedia()->get($parentId);

                    if (!$Parent instanceof Folder) {
                        throw new QUI\Exception('Media item is not a folder.');
                    }

                    $filename = basename($filename);

                    if ($filename === '') {
                        throw new QUI\Exception('Filename is empty.');
                    }

                    $content = base64_decode($contentBase64, true);

                    if ($content === false) {
                        throw new QUI\Exception('contentBase64 is not valid base64.');
                    }

                    $tmpDir = QUI::getTemp()->createFolder('mcp-media-upload');
                    $tmpFile = $tmpDir . uniqid('', true) . '-' . $filename;

                    if (file_put_contents($tmpFile, $content) === false) {
                        throw new QUI\Exception('Could not write temporary upload file.');
                    }

                    $File = $Parent->uploadFile(
                        $tmpFile,
                        self::parseOverwrite($overwrite),
                        Server::getRequestUser()
                    );

                    return [
                        'uploaded' => true,
                        'file' => self::parseMediaItem($File, true)
                    ];
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                } finally {
                    if (is_string($tmpFile) && file_exists($tmpFile)) {
                        unlink($tmpFile);
                    }
                }
            },
            name: 'quiqqer_media_upload',
            description: 'Uploads a base64 encoded file into a media folder.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'parentId', 'filename', 'contentBase64'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'description' => 'Parent media folder ID.', 'minimum' => 1],
                    'filename' => ['type' => 'string', 'description' => 'Target file name.'],
                    'contentBase64' => ['type' => 'string', 'description' => 'Base64 encoded file content.'],
                    'overwrite' => [
                        'type' => 'string',
                        'description' => 'Overwrite behavior.',
                        'enum' => ['none', 'overwrite', 'destroy'],
                        'default' => 'none'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Project language.']
                ]
            ]
        );
    }

    protected static function parseOverwrite(?string $overwrite): int
    {
        return match ($overwrite) {
            'overwrite' => Folder::FILE_OVERWRITE_TRUE,
            'destroy' => Folder::FILE_OVERWRITE_DESTROY,
            default => Folder::FILE_OVERWRITE_NONE,
        };
    }
}
