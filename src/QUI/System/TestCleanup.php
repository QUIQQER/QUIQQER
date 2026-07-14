<?php

namespace QUI\System;

use QUI;
use Throwable;

use function array_filter;
use function array_keys;
use function array_values;
use function defined;
use function file_exists;
use function glob;
use function implode;
use function is_dir;
use function is_file;
use function is_string;
use function str_starts_with;
use function unlink;

use const PHP_SAPI;
use const SIGINT;
use const SIGTERM;
use const SIG_IGN;

/**
 * Removes PHPUnit projects after interrupted or completed test runs.
 */
final class TestCleanup
{
    public const EVENT = 'onQuiqqerTestCleanup';

    private const PROJECT_PREFIX = 'phpunit';

    private static bool $cleanupCompleted = false;
    private static bool $cleanupRunning = false;
    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered || PHP_SAPI !== 'cli') {
            return;
        }

        self::$registered = true;

        self::registerSignalHandlers();
        register_shutdown_function(static function (): void {
            if (self::$cleanupCompleted) {
                return;
            }

            try {
                self::execute();
            } catch (Throwable $Exception) {
                error_log('[QUIQQER test cleanup] ' . $Exception->getMessage());
            }
        });
    }

    /**
     * @return string[] Removed PHPUnit project names.
     */
    public static function execute(): array
    {
        if (self::$cleanupRunning || self::$cleanupCompleted) {
            return [];
        }

        self::$cleanupRunning = true;

        try {
            $EventException = null;

            try {
                QUI::getEvents()->fireEvent(self::EVENT);
            } catch (Throwable $Exception) {
                $EventException = $Exception;
            }

            $projects = self::cleanupProjects();

            if ($EventException !== null) {
                throw $EventException;
            }

            return $projects;
        } finally {
            self::$cleanupRunning = false;
            self::$cleanupCompleted = true;
        }
    }

    public static function cleanupProject(string $projectName): void
    {
        if (!self::isPhpUnitProjectName($projectName)) {
            throw new QUI\Exception('Only PHPUnit projects can be removed by the test cleanup.');
        }

        $errors = [];
        $steps = [
            static fn() => self::deleteProjectConfig($projectName),
            static fn() => self::dropProjectTables($projectName),
            static fn() => self::deleteProjectPermissionRows($projectName),
            static fn() => self::deleteProjectLocaleFiles($projectName),
            static fn() => self::moveProjectDirectoriesToTemp($projectName),
            static function (): void {
                QUI\Projects\Manager::cleanup();
                QUI\Projects\Manager::$Standard = null;
                QUI\Cache\Manager::clearProjectsCache();
            }
        ];

        foreach ($steps as $step) {
            try {
                $step();
            } catch (Throwable $Exception) {
                $errors[] = $Exception->getMessage();
            }
        }

        if ($errors !== []) {
            throw new QUI\Exception(
                'PHPUnit project cleanup failed for "' . $projectName . '": ' . implode('; ', $errors)
            );
        }
    }

    private static function handleSignal(int $signal): never
    {
        pcntl_signal(SIGINT, SIG_IGN);
        pcntl_signal(SIGTERM, SIG_IGN);

        try {
            self::execute();
        } catch (Throwable $Exception) {
            error_log('[QUIQQER test cleanup] ' . $Exception->getMessage());
        }

        exit(128 + $signal);
    }

    private static function registerSignalHandlers(): void
    {
        if (
            !function_exists('pcntl_async_signals') ||
            !function_exists('pcntl_signal') ||
            !defined('SIGINT') ||
            !defined('SIGTERM')
        ) {
            return;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGINT, static fn(int $signal): never => self::handleSignal($signal));
        pcntl_signal(SIGTERM, static fn(int $signal): never => self::handleSignal($signal));
    }

    /**
     * @return string[]
     */
    private static function cleanupProjects(): array
    {
        $projects = array_values(array_filter(
            array_keys(QUI\Projects\Manager::getConfig()->toArray()),
            static fn(mixed $name): bool => is_string($name)
                && self::isPhpUnitProjectName($name)
        ));

        foreach ($projects as $projectName) {
            self::cleanupProject($projectName);
        }

        return $projects;
    }

    private static function isPhpUnitProjectName(string $projectName): bool
    {
        return $projectName === self::PROJECT_PREFIX
            || str_starts_with($projectName, self::PROJECT_PREFIX . '_');
    }

    private static function dropProjectTables(string $projectName): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $tablePrefix = QUI_DB_PRFX . $projectName . '_';

        foreach ($SchemaManager->listTableNames() as $tableName) {
            if (!str_starts_with($tableName, $tablePrefix)) {
                continue;
            }

            $SchemaManager->dropTable($tableName);
        }
    }

    private static function deleteProjectPermissionRows(string $projectName): void
    {
        $Connection = QUI::getDataBaseConnection();
        $SchemaManager = QUI::getSchemaManager();
        $permissionProjectTable = QUI::getDBTableName(QUI\Permissions\Manager::TABLE) . '2projects';
        $permissionSitesTable = QUI::getDBTableName(QUI\Permissions\Manager::TABLE) . '2sites';

        if ($SchemaManager->tablesExist([$permissionProjectTable])) {
            $Connection->delete($permissionProjectTable, ['project' => $projectName]);
        }

        if ($SchemaManager->tablesExist([$permissionSitesTable])) {
            $Connection->delete($permissionSitesTable, ['project' => $projectName]);
        }
    }

    private static function deleteProjectConfig(string $projectName): void
    {
        $Config = QUI\Projects\Manager::getConfig();
        $Config->del($projectName);
        $Config->save();
    }

    private static function deleteProjectLocaleFiles(string $projectName): void
    {
        foreach (glob(VAR_DIR . 'locale/*/LC_MESSAGES/project_' . $projectName . '.ini.php') ?: [] as $localeFile) {
            if (is_file($localeFile)) {
                unlink($localeFile);
            }
        }

        $localeBinDirectory = VAR_DIR . 'locale/bin/project/' . $projectName;

        if (is_dir($localeBinDirectory)) {
            QUI::getTemp()->moveToTemp($localeBinDirectory);
        }
    }

    private static function moveProjectDirectoriesToTemp(string $projectName): void
    {
        foreach (
            [
            CMS_DIR . 'media/sites/' . $projectName,
            CMS_DIR . 'media/cache/' . $projectName,
            USR_DIR . $projectName
            ] as $directory
        ) {
            if (!file_exists($directory)) {
                continue;
            }

            QUI::getTemp()->moveToTemp($directory);
        }
    }
}
