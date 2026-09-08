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
use function getenv;
use function getmypid;
use function implode;
use function is_dir;
use function is_file;
use function is_string;
use function putenv;
use function str_starts_with;
use function unlink;

use const PHP_SAPI;
use const SIGINT;
use const SIGTERM;
use const SIG_IGN;

/**
 * Removes projects explicitly reserved by the current PHPUnit process.
 */
final class TestCleanup
{
    public const EVENT = 'onQuiqqerTestCleanup';

    private const PROJECT_PREFIX = 'phpunit';
    private const OWNER_PROCESS_ID_ENVIRONMENT_VARIABLE = 'QUIQQER_TEST_CLEANUP_OWNER_PROCESS_ID';

    private static bool $cleanupCompleted = false;
    private static bool $cleanupRunning = false;
    private static bool $registered = false;

    /** @var array<string, array{processId: int, handle: resource}> */
    private static array $projectLocks = [];

    public static function register(): void
    {
        if (
            self::$registered
            || PHP_SAPI !== 'cli'
            || !self::isCleanupOwnerProcess()
        ) {
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

    private static function isCleanupOwnerProcess(): bool
    {
        $processId = getmypid();

        if ($processId === false) {
            return true;
        }

        $ownerProcessId = getenv(self::OWNER_PROCESS_ID_ENVIRONMENT_VARIABLE);

        if ($ownerProcessId !== false) {
            return $ownerProcessId === (string)$processId;
        }

        putenv(self::OWNER_PROCESS_ID_ENVIRONMENT_VARIABLE . '=' . $processId);

        return true;
    }

    /**
     * @return string[] Removed PHPUnit project names.
     */
    public static function execute(): array
    {
        if (!self::isCleanupOwnerProcess() || self::$cleanupRunning || self::$cleanupCompleted) {
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

    /**
     * Reserve a test project before creating it. The OS releases the lock if the process dies.
     */
    public static function claimProject(string $projectName): bool
    {
        self::assertTestProjectName($projectName);

        if (isset(self::$projectLocks[$projectName])) {
            return self::$projectLocks[$projectName]['processId'] === getmypid();
        }

        if (array_key_exists($projectName, QUI\Projects\Manager::getConfig()->toArray())) {
            return false;
        }

        $directory = dirname(self::projectLockFile($projectName));
        QUI\Utils\System\File::mkdir($directory);
        $Lock = self::openProjectLock($projectName);

        if ($Lock === false) {
            return false;
        }

        self::$projectLocks[$projectName] = ['processId' => (int)getmypid(), 'handle' => $Lock];
        return true;
    }

    /** @return bool False when another live test process owns the project. */
    public static function cleanupProject(string $projectName): bool
    {
        self::assertTestProjectName($projectName);
        $Lock = self::acquireCleanupLock($projectName);

        if ($Lock === false) {
            return false;
        }

        try {
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
            return true;
        } finally {
            if (isset(self::$projectLocks[$projectName])) {
                $OwnedLock = self::$projectLocks[$projectName]['handle'];
                flock($OwnedLock, LOCK_UN);
                fclose($OwnedLock);
                unset(self::$projectLocks[$projectName]);
            }
        }
    }

    private static function assertTestProjectName(string $projectName): void
    {
        if (!self::isPhpUnitProjectName($projectName)) {
            throw new QUI\Exception('Only PHPUnit projects can be removed by the test cleanup.');
        }
    }

    private static function projectLockFile(string $projectName): string
    {
        return VAR_DIR . 'phpunit-project-locks/' . hash('sha256', $projectName) . '.lock';
    }

    /** @return resource|false */
    private static function openProjectLock(string $projectName): mixed
    {
        $Lock = fopen(self::projectLockFile($projectName), 'c');

        if ($Lock === false) {
            throw new \RuntimeException('Could not open PHPUnit project lock.');
        }

        if (!flock($Lock, LOCK_EX | LOCK_NB)) {
            fclose($Lock);
            return false;
        }

        return $Lock;
    }

    private static function acquireCleanupLock(string $projectName): bool
    {
        if (isset(self::$projectLocks[$projectName])) {
            return self::$projectLocks[$projectName]['processId'] === getmypid();
        }

        return false;
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
        $projects = array_keys(array_filter(
            self::$projectLocks,
            static fn(array $owner): bool => $owner['processId'] === getmypid()
        ));

        $removed = [];

        foreach ($projects as $projectName) {
            if (self::cleanupProject($projectName)) {
                $removed[] = $projectName;
            }
        }

        return $removed;
    }

    private static function isPhpUnitProjectName(string $projectName): bool
    {
        return $projectName === self::PROJECT_PREFIX
            || str_starts_with($projectName, self::PROJECT_PREFIX . '_');
    }

    private static function dropProjectTables(string $projectName): void
    {
        $SchemaManager = QUI::getSchemaManager();
        $projectNames = array_keys(QUI\Projects\Manager::getConfig()->toArray());

        foreach (self::projectTables($projectName, $SchemaManager->listTableNames(), $projectNames) as $tableName) {
            $SchemaManager->dropTable($tableName);
        }
    }

    /**
     * @param list<string> $tableNames
     * @param list<string> $projectNames
     * @return list<string>
     */
    private static function projectTables(string $projectName, array $tableNames, array $projectNames): array
    {
        $prefix = QUI_DB_PRFX . $projectName . '_';
        $otherPrefixes = [];

        foreach ($projectNames as $otherName) {
            if ($otherName !== $projectName && str_starts_with($otherName . '_', $projectName . '_')) {
                $otherPrefixes[] = QUI_DB_PRFX . $otherName . '_';
            }
        }

        return array_values(array_filter($tableNames, static function (string $table) use ($prefix, $otherPrefixes): bool {
            if (!str_starts_with($table, $prefix)) {
                return false;
            }

            foreach ($otherPrefixes as $otherPrefix) {
                if (str_starts_with($table, $otherPrefix)) {
                    return false;
                }
            }

            return true;
        }));
    }

    private static function deleteProjectPermissionRows(string $projectName): void
    {
        $Connection = QUI::getDataBaseConnection();
        $SchemaManager = QUI::getSchemaManager();
        $permissionProjectTable = QUI::getDBTableName(QUI\Permissions\Manager::TABLE) . '2projects';
        $permissionSitesTable = QUI::getDBTableName(QUI\Permissions\Manager::TABLE) . '2sites';
        $permissionMediaTable = QUI::getDBTableName(QUI\Permissions\Manager::TABLE) . '2media';

        if ($SchemaManager->tablesExist([$permissionProjectTable])) {
            $Connection->delete($permissionProjectTable, ['project' => $projectName]);
        }

        if ($SchemaManager->tablesExist([$permissionSitesTable])) {
            $Connection->delete($permissionSitesTable, ['project' => $projectName]);
        }

        if ($SchemaManager->tablesExist([$permissionMediaTable])) {
            $Connection->delete($permissionMediaTable, ['project' => $projectName]);
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
        // Otherwise a later translator publish recreates files for deleted fixtures.
        $Connection = QUI::getDataBaseConnection();
        $table = QUI\Translator::table();

        if (QUI::getSchemaManager()->tablesExist([$table])) {
            $ids = $Connection->createQueryBuilder()
                ->select('id')
                ->from(QUI\Utils\Doctrine::quoteIdentifier($table))
                ->where(QUI\Utils\Doctrine::quoteIdentifier('groups') . ' = :group')
                ->setParameter('group', 'project/' . $projectName)
                ->executeQuery()
                ->fetchFirstColumn();

            foreach ($ids as $id) {
                QUI\Translator::deleteById((int)$id);
            }
        }

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
            USR_DIR . $projectName,
            VAR_DIR . 'media/trash/' . $projectName
            ] as $directory
        ) {
            if (!file_exists($directory)) {
                continue;
            }

            QUI::getTemp()->moveToTemp($directory);
        }
    }
}
