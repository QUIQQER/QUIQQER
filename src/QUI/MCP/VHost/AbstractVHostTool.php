<?php

/**
 * This file contains the \QUI\MCP\VHost\AbstractVHostTool
 */

namespace QUI\MCP\VHost;

use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use QUI\System\VhostManager;

use function is_scalar;
use function is_string;
use function strtolower;
use function trim;

abstract class AbstractVHostTool extends AbstractTool
{
    protected const MANAGE_VHOSTS_PERMISSION = 'quiqqer.core.mcp.manageVhosts';

    protected static function checkVHostWritePermission(): void
    {
        self::checkCorePermission();

        Permission::checkPermission(
            self::MANAGE_VHOSTS_PERMISSION,
            Server::getRequestUser()
        );
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected static function parseVHost(string $host, array $data): array
    {
        return [
            'host' => $host,
            'project' => is_string($data['project'] ?? null) ? $data['project'] : '',
            'rootLanguage' => is_string($data['lang'] ?? null) ? $data['lang'] : '',
            'pathLanguages' => VhostManager::parsePathLanguages(
                $data[VhostManager::PATH_LANGUAGES_CONFIG_KEY] ?? ''
            ),
            'template' => is_string($data['template'] ?? null) ? $data['template'] : '',
            'error' => is_string($data['error'] ?? null) ? $data['error'] : '',
            'httpsHost' => is_string($data['httpshost'] ?? null) ? $data['httpshost'] : ''
        ];
    }

    /**
     * Build data accepted by VhostManager::editVhost().
     *
     * @param array<string, mixed> $existing
     * @param array<int, mixed>|null $pathLanguages
     *
     * @return array<string, string>
     */
    protected static function buildVHostData(
        array $existing,
        ?string $project = null,
        ?string $rootLanguage = null,
        ?array $pathLanguages = null,
        ?string $template = null,
        ?string $error = null,
        ?string $httpsHost = null
    ): array {
        $project ??= is_string($existing['project'] ?? null) ? $existing['project'] : '';
        $rootLanguage ??= is_string($existing['lang'] ?? null) ? $existing['lang'] : '';
        $template ??= is_string($existing['template'] ?? null) ? $existing['template'] : '';
        $error ??= is_string($existing['error'] ?? null) ? $existing['error'] : '';
        $httpsHost ??= is_string($existing['httpshost'] ?? null) ? $existing['httpshost'] : '';

        if ($pathLanguages === null) {
            $pathLanguages = VhostManager::parsePathLanguages(
                $existing[VhostManager::PATH_LANGUAGES_CONFIG_KEY] ?? ''
            );
        }

        $normalizedPathLanguages = [];

        foreach ($pathLanguages as $language) {
            if (!is_scalar($language)) {
                continue;
            }

            $normalizedPathLanguages[] = strtolower(trim((string)$language));
        }

        return [
            'project' => trim($project),
            'lang' => strtolower(trim($rootLanguage)),
            VhostManager::PATH_LANGUAGES_CONFIG_KEY => implode(
                ',',
                VhostManager::parsePathLanguages($normalizedPathLanguages)
            ),
            'template' => trim($template),
            'error' => trim($error),
            'httpshost' => trim($httpsHost)
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getVHostOrFail(VhostManager $Manager, string $host): array
    {
        $data = $Manager->getVhost($host);

        if (!is_array($data)) {
            throw new QUI\Exception('VHost not found: ' . $host, 404);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getVHostInputProperties(): array
    {
        return [
            'host' => ['type' => 'string', 'description' => 'VHost domain name.'],
            'project' => ['type' => 'string', 'description' => 'Project name.'],
            'rootLanguage' => [
                'type' => 'string',
                'description' => 'Two-letter project language served at the VHost root.',
                'pattern' => '^[a-z]{2}$'
            ],
            'pathLanguages' => [
                'type' => 'array',
                'description' => 'Project languages served below /<language>/.',
                'items' => [
                    'type' => 'string',
                    'pattern' => '^[a-z]{2}$'
                ]
            ],
            'template' => ['type' => 'string', 'description' => 'Optional template package name.'],
            'error' => [
                'type' => 'string',
                'description' => 'Optional error site as project,lang,id.'
            ],
            'httpsHost' => ['type' => 'string', 'description' => 'Optional HTTPS host name.']
        ];
    }
}
