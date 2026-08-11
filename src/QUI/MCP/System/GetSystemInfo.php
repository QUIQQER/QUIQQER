<?php

/**
 * This file contains the \QUI\MCP\System\GetSystemInfo
 */

namespace QUI\MCP\System;

use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Builder;
use QUI;
use QUI\AI\MCP\Server;
use QUI\AI\MCP\ToolHelper;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use Throwable;

class GetSystemInfo extends AbstractTool
{
    protected const SYSTEM_INFO_PERMISSION = 'quiqqer.core.mcp.viewSystemInfo';

    public function register(Builder $serverBuilder): void
    {
        $serverBuilder->addTool(
            function (): CallToolResult | array {
                try {
                    self::checkCorePermission();
                    Permission::checkPermission(
                        self::SYSTEM_INFO_PERMISSION,
                        Server::getRequestUser()
                    );

                    return self::getSystemInformation();
                } catch (Throwable $Exception) {
                    return ToolHelper::parseExceptionToResult($Exception);
                }
            },
            name: 'quiqqer_system_info_get',
            description: 'Returns protected runtime, database, web server, QUIQQER and installed package versions.',
            inputSchema: [
                'type' => 'object',
                'additionalProperties' => false,
                'properties' => []
            ]
        );
    }

    /**
     * @return array{
     *     php: array{version: string, sapi: string},
     *     database: array{name: string, version: string},
     *     webServer: array{software: string},
     *     quiqqer: array{version: string},
     *     packageCount: int,
     *     packages: array<int, array{name: string, version: string, type: string}>
     * }
     */
    protected static function getSystemInformation(): array
    {
        $Connection = QUI::getDataBaseConnection();
        $databaseVersion = $Connection->getServerVersion();
        $databasePlatform = $Connection->getDatabasePlatform()::class;
        $PackageManager = QUI::getPackageManager();
        $packages = self::normalizePackages($PackageManager->getInstalled());

        return [
            'php' => [
                'version' => PHP_VERSION,
                'sapi' => PHP_SAPI
            ],
            'database' => [
                'name' => self::detectDatabaseName($databasePlatform, $databaseVersion),
                'version' => $databaseVersion
            ],
            'webServer' => [
                'software' => self::getWebServerSoftware()
            ],
            'quiqqer' => [
                'version' => $PackageManager->getVersion()
            ],
            'packageCount' => count($packages),
            'packages' => $packages
        ];
    }

    protected static function detectDatabaseName(string $platformClass, string $serverVersion): string
    {
        $databaseIdentity = strtolower($platformClass . ' ' . $serverVersion);

        return match (true) {
            str_contains($databaseIdentity, 'mariadb') => 'MariaDB',
            str_contains($databaseIdentity, 'mysql') => 'MySQL',
            str_contains($databaseIdentity, 'postgres') => 'PostgreSQL',
            str_contains($databaseIdentity, 'sqlite') => 'SQLite',
            str_contains($databaseIdentity, 'oracle') => 'Oracle',
            str_contains($databaseIdentity, 'sqlserver'),
            str_contains($databaseIdentity, 'mssql') => 'Microsoft SQL Server',
            default => 'unknown'
        };
    }

    /**
     * @param array<int, array<string, mixed>> $installedPackages
     * @return array<int, array{name: string, version: string, type: string}>
     */
    protected static function normalizePackages(array $installedPackages): array
    {
        $packages = [];

        foreach ($installedPackages as $package) {
            if (!isset($package['name']) || !is_string($package['name']) || $package['name'] === '') {
                continue;
            }

            $packages[] = [
                'name' => $package['name'],
                'version' => is_scalar($package['version'] ?? null) ? (string)$package['version'] : '',
                'type' => is_scalar($package['type'] ?? null) ? (string)$package['type'] : ''
            ];
        }

        usort(
            $packages,
            static fn(array $packageA, array $packageB): int => strcmp($packageA['name'], $packageB['name'])
        );

        return $packages;
    }

    protected static function getWebServerSoftware(): string
    {
        $serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? null;

        if (!is_string($serverSoftware) || trim($serverSoftware) === '') {
            return 'unknown';
        }

        return trim($serverSoftware);
    }
}
