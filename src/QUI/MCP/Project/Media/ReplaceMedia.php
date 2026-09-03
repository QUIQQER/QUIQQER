<?php

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\Projects\Media\Folder;
use Throwable;

class ReplaceMedia extends AbstractMediaTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $id,
                string $filename,
                string $contentBase64
            ): CallToolResult | array {
                $tmpFile = null;

                try {
                    self::checkCorePermission();
                    $Item = self::getMediaItem($project, $id);

                    if ($Item instanceof Folder) {
                        throw new QUI\Exception('Only media files can be replaced.');
                    }

                    self::checkMediaPermission($Item, 'quiqqer.projects.media.edit');
                    $filename = basename(str_replace('\\', '/', $filename));

                    if ($filename === '') {
                        throw new QUI\Exception('Filename is empty.');
                    }

                    $content = base64_decode($contentBase64, true);

                    if ($content === false) {
                        throw new QUI\Exception('contentBase64 is not valid base64.');
                    }

                    if (strlen($content) > self::MAX_REPLACE_BYTES) {
                        throw new QUI\Exception('Replacement file exceeds the 50 MiB MCP limit.');
                    }

                    $tmpDir = QUI::getTemp()->createFolder('mcp-media-replace');
                    $tmpFile = $tmpDir . uniqid('', true) . '-' . $filename;

                    if (file_put_contents($tmpFile, $content) === false) {
                        throw new QUI\Exception('Could not write temporary replacement file.');
                    }

                    $File = self::getMedia($project)->replace(
                        $id,
                        $tmpFile,
                        Server::getRequestUser()
                    );
                    $tmpFile = null;

                    return [
                        'replaced' => true,
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
            name: 'quiqqer_media_replace',
            description: 'Replaces the physical content of one media file with Base64 encoded content.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'id', 'filename', 'contentBase64'],
                'properties' => [
                    'project' => ['type' => 'string', 'minLength' => 1],
                    'id' => ['type' => 'integer', 'minimum' => 1],
                    'filename' => ['type' => 'string', 'minLength' => 1],
                    'contentBase64' => ['type' => 'string', 'minLength' => 1]
                ]
            ]
        );
    }
}
