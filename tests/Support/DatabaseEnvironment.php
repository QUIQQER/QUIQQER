<?php

namespace QUITests\Support;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use QUI;

final class DatabaseEnvironment
{
    public const MODE_CI_DATABASE = 'ci-database';
    public const MODE_SQLITE = 'sqlite';

    /** @param array<string, string|false> $environment */
    public static function determineMode(array $environment): string
    {
        return ($environment['GITLAB_CI'] ?? false) === 'true'
            ? self::MODE_CI_DATABASE
            : self::MODE_SQLITE;
    }

    public static function getMode(): string
    {
        return self::determineMode(getenv());
    }

    public static function usesCiDatabase(): bool
    {
        return self::getMode() === self::MODE_CI_DATABASE;
    }

    public static function createConnection(?string $sqlitePath = null): Connection
    {
        if (self::usesCiDatabase()) {
            return DriverManager::getConnection(QUI::getDataBaseConnection()->getParams());
        }

        return DriverManager::getConnection($sqlitePath === null
            ? ['driver' => 'pdo_sqlite', 'memory' => true]
            : ['driver' => 'pdo_sqlite', 'path' => $sqlitePath]);
    }
}
