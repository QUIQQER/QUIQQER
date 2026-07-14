<?php

/**
 * This file contains the \QUI\MCP\Project\Media\CreateUploadSession
 */

namespace QUI\MCP\Project\Media;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\AI\MCP\Upload\DirectUploadService;
use QUI\MCP\AbstractTool;
use QUI\Projects\Media\Folder;
use Throwable;

class CreateUploadSession extends AbstractTool
{
    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $project,
                int $parentId,
                string $filename,
                string | null $overwrite = null,
                string | null $lang = null,
                int | null $maxBytes = null,
                array | null $allowedMimeTypes = null
            ): CallToolResult | array {
                try {
                    self::checkCorePermission();

                    $Parent = self::getProject($project, $lang)->getMedia()->get($parentId);

                    if (!$Parent instanceof Folder) {
                        throw new QUI\Exception('Media item is not a folder.');
                    }

                    $Parent->checkPermission('quiqqer.projects.media.upload', Server::getRequestUser());

                    $filename = basename(str_replace('\\', '/', $filename));

                    if ($filename === '') {
                        throw new QUI\Exception('Filename is empty.');
                    }

                    $session = (new DirectUploadService())->createSession(
                        $filename,
                        [
                            'type' => 'quiqqer.media',
                            'project' => $project,
                            'parentId' => $parentId,
                            'overwrite' => $overwrite ?: 'none',
                            'lang' => $lang
                        ],
                        $maxBytes,
                        $allowedMimeTypes
                    );

                    $session['finalizeTool'] = 'quiqqer_media_finalize_upload';
                    $session['statusTool'] = 'quiqqer_media_get_upload_session';

                    return $session;
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_media_create_upload_session',
            description: 'Creates a short-lived direct HTTP upload session for a media file without Base64 payloads.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['project', 'parentId', 'filename'],
                'properties' => [
                    'project' => ['type' => 'string', 'description' => 'Project name.'],
                    'parentId' => ['type' => 'integer', 'description' => 'Parent media folder ID.', 'minimum' => 1],
                    'filename' => ['type' => 'string', 'description' => 'Target file name.'],
                    'overwrite' => [
                        'type' => 'string',
                        'description' => 'Overwrite behavior.',
                        'enum' => ['none', 'overwrite', 'destroy'],
                        'default' => 'none'
                    ],
                    'lang' => ['type' => 'string', 'description' => 'Project language.'],
                    'maxBytes' => [
                        'type' => 'integer',
                        'description' => 'Optional per-session size limit. The server caps this at 52428800 bytes.',
                        'minimum' => 1,
                        'maximum' => 52428800
                    ],
                    'allowedMimeTypes' => [
                        'type' => 'array',
                        'description' => 'Optional list of allowed MIME types. Wildcards like image/* are supported.',
                        'items' => ['type' => 'string']
                    ]
                ]
            ]
        );
    }
}
