<?php

/**
 * This file contains the \QUI\MCP\System\Update
 */

namespace QUI\MCP\System;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use QUI\System\Update\RunEntrypoint;
use QUI\System\Update\RunLauncherFactory;
use QUI\System\Update\RunRepository;
use QUI\System\Update\RunState;
use Throwable;

use const CMS_DIR;
use const DIRECTORY_SEPARATOR;
use const VAR_DIR;

class Update extends AbstractTool
{
    protected const UPDATE_PERMISSION = 'quiqqer.core.mcp.updateAllowed';

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (
                string $action,
                string | null $id = null,
                int | null $limit = null
            ): CallToolResult | array {
                try {
                    self::checkUpdatePermission();

                    return match ($action) {
                        'prepare' => self::prepare(false),
                        'start' => self::prepare(true),
                        'status' => self::status((string)$id),
                        'active' => self::active(),
                        'history' => self::history($limit),
                        'cancel' => self::cancel((string)$id),
                        default => throw new \InvalidArgumentException('Unknown update action.')
                    };
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_system_update',
            description: 'Prepares, starts and monitors QUIQQER system update runs via MCP. Requires core MCP permission and dedicated MCP update permission.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'required' => ['action'],
                'properties' => [
                    'action' => [
                        'type' => 'string',
                        'description' => 'Update action. prepare creates a run and returns tokenized URLs; start creates and starts a background run; status polls one run; active lists active runs; history lists recent runs; cancel cancels one run.',
                        'enum' => ['prepare', 'start', 'status', 'active', 'history', 'cancel']
                    ],
                    'id' => [
                        'type' => 'string',
                        'description' => 'Update run ID. Required for status and cancel.'
                    ],
                    'limit' => [
                        'type' => 'integer',
                        'description' => 'Maximum number of history entries.',
                        'minimum' => 1,
                        'maximum' => 100,
                        'default' => 20
                    ]
                ]
            ]
        );
    }

    private static function checkUpdatePermission(): void
    {
        self::checkCorePermission();

        Permission::checkPermission(
            self::UPDATE_PERMISSION,
            Server::getRequestUser()
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function prepare(bool $start): array
    {
        $Repository = self::createRepository();
        $runs = $Repository->cleanupAndFindActive(time(), 86400);

        if (!empty($runs['active'])) {
            $State = $runs['active'][0];

            return [
                'success' => true,
                'prepared' => false,
                'started' => false,
                'active' => true,
                'run' => $State->toPublicArray(),
                'deleted' => $runs['deleted'],
                'maintenance' => self::getMaintenanceInfo()
            ];
        }

        $Launch = RunLauncherFactory::createDefault()->create(null, [
            'type' => 'mcp',
            'arguments' => []
        ]);
        $Run = $Launch->getRun();
        $State = $Run->getState();
        $token = $Run->getToken();
        $started = false;

        if ($start) {
            $StartedState = (new RunEntrypoint())->startCliProcess(
                $State->getId(),
                self::getRunRoot(),
                $token,
                $Repository
            );

            if ($StartedState !== null) {
                $State = $StartedState;
                $started = true;
            }
        }

        return [
            'success' => true,
            'prepared' => true,
            'started' => $started,
            'active' => false,
            'id' => $State->getId(),
            'token' => $token,
            'webUrl' => $Launch->getWebUrl(),
            'jsonRunUrl' => self::withQuery($Launch->getWebUrl(), [
                'output' => 'json',
                'action' => 'run'
            ]),
            'jsonStatusUrl' => self::withQuery($Launch->getWebUrl(), [
                'output' => 'json',
                'action' => 'status'
            ]),
            'sseUrl' => self::withQuery($Launch->getWebUrl(), [
                'output' => 'sse'
            ]),
            'run' => $State->toPublicArray(),
            'deleted' => $runs['deleted'],
            'maintenance' => self::getMaintenanceInfo()
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function status(string $id): array
    {
        if ($id === '') {
            throw new \InvalidArgumentException('Missing update run ID.');
        }

        $State = self::createRepository()->load($id);

        return [
            'success' => true,
            'run' => $State->toPublicArray(),
            'maintenance' => self::getMaintenanceInfo(),
            'log' => self::readRunLog($id)
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function active(): array
    {
        $runs = self::createRepository()->cleanupAndFindActive(time(), 86400);

        return [
            'success' => true,
            'active' => array_map(
                static fn (RunState $State): array => $State->toPublicArray(),
                $runs['active']
            ),
            'deleted' => $runs['deleted'],
            'maintenance' => self::getMaintenanceInfo()
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function history(?int $limit): array
    {
        $limit = min(100, max(1, $limit ?? 20));

        return [
            'success' => true,
            'history' => array_map(
                static fn (RunState $State): array => $State->toPublicArray(),
                self::createRepository()->list($limit)
            ),
            'maintenance' => self::getMaintenanceInfo()
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function cancel(string $id): array
    {
        if ($id === '') {
            throw new \InvalidArgumentException('Missing update run ID.');
        }

        $State = self::createRepository()->cancel($id);
        $process = $State->getProcess();
        $pid = is_array($process) ? (int)($process['pid'] ?? 0) : 0;
        $signalSent = false;

        if ($pid > 0 && function_exists('posix_kill')) {
            $isRunning = posix_kill($pid, 0);

            if ($isRunning) {
                $signalSent = posix_kill($pid, 15);
            }
        }

        return [
            'success' => true,
            'run' => $State->toPublicArray(),
            'maintenance' => self::getMaintenanceInfo(),
            'signalSent' => $signalSent
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function getMaintenanceInfo(): array
    {
        $enabled = file_exists(CMS_DIR . 'maintenance.html');

        return [
            'active' => $enabled,
            'message' => $enabled
                ? 'QUIQQER maintenance mode is active. MCP remains available for update monitoring.'
                : null
        ];
    }

    private static function createRepository(): RunRepository
    {
        return new RunRepository(self::getRunRoot());
    }

    private static function getRunRoot(): string
    {
        return rtrim(VAR_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'update/runs/';
    }

    /**
     * @param array<string, mixed> $params
     */
    private static function withQuery(string $url, array $params): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url . $separator . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }

    private static function readRunLog(string $id): string
    {
        try {
            RunState::assertValidIdentifier($id);
        } catch (Throwable) {
            return '';
        }

        $file = self::getRunRoot() . $id . DIRECTORY_SEPARATOR . 'runner.log';

        if (!is_file($file)) {
            return '';
        }

        $content = file_get_contents($file);

        if ($content === false) {
            return '';
        }

        return self::cleanRunLog($content);
    }

    private static function cleanRunLog(string $content): string
    {
        $content = preg_replace('/\x1b\[[0-9;]*m/', '', $content) ?? $content;
        $jsonPosition = strpos($content, '{"success":');

        if ($jsonPosition !== false) {
            $content = substr($content, 0, $jsonPosition);
        }

        return rtrim($content);
    }
}
