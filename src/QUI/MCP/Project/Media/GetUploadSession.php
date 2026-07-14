<?php

/**
 * This file contains the \QUI\MCP\Project\Media\GetUploadSession
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\ToolHelper;
use QUI\AI\MCP\Upload\DirectUploadService;
use QUI\MCP\AbstractTool;
use Throwable;

class GetUploadSession extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (string $uploadId): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $session = (new DirectUploadService())->getSessionStatus($uploadId);
                    $metadata = $session['metadata'] ?? [];

                    if (!is_array($metadata) || ($metadata['type'] ?? null) !== 'quiqqer.media') {
                        throw new QUI\Exception('Upload session is not a media upload session.');
                    }

                    return $session;
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_get_upload_session',
            description: 'Returns the status of a direct media upload session.',
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
}
