<?php

namespace QUI\Projects;

use RuntimeException;
use QUI;
use QUI\Permissions\Permission;
use ReflectionProperty;
use Throwable;

final class ProjectTestHelper
{
    private const BASE_PROJECT_NAME = 'phpunit';
    private const LANGUAGE = 'de';

    private static ?string $projectName = null;
    private static ?string $createdProjectName = null;
    private static bool $cleanupRegistered = false;

    public static function getProject(): Project
    {
        if (self::$projectName === null) {
            self::createProject();
        }

        return QUI::getProject(self::$projectName, self::LANGUAGE);
    }

    public static function getProjectName(): string
    {
        if (self::$projectName === null) {
            self::createProject();
        }

        return self::$projectName;
    }

    public static function cleanup(): void
    {
        if (self::$createdProjectName === null) {
            return;
        }

        $projectName = self::$createdProjectName;

        try {
            self::withSystemUser(static function () use ($projectName): void {
                self::dropProjectTables($projectName);
                self::deleteProjectPermissionRows($projectName);
                self::deleteProjectConfig($projectName);
                self::moveProjectDirectoriesToTemp($projectName);

                Manager::cleanup();
                Manager::$Standard = null;
                QUI\Cache\Manager::clearProjectsCache();
            });
        } catch (Throwable) {
            // Cleanup must not hide the actual PHPUnit result.
        } finally {
            self::$projectName = null;
            self::$createdProjectName = null;
        }
    }

    private static function createProject(): void
    {
        self::registerCleanup();
        self::skipIfDatabaseIsUnavailable();

        $projectName = self::getAvailableProjectName();
        self::$createdProjectName = $projectName;

        try {
            self::withSystemUser(static function () use ($projectName): void {
                Manager::createProject($projectName, self::LANGUAGE, [self::LANGUAGE]);
            });
        } catch (Throwable $Exception) {
            self::cleanup();
            throw $Exception;
        }

        self::$projectName = $projectName;
    }

    private static function registerCleanup(): void
    {
        if (self::$cleanupRegistered) {
            return;
        }

        self::$cleanupRegistered = true;
        register_shutdown_function(static function (): void {
            self::cleanup();
        });
    }

    private static function skipIfDatabaseIsUnavailable(): void
    {
        try {
            QUI::getDataBaseConnection()->executeQuery('SELECT 1')->free();
        } catch (Throwable $Exception) {
            throw new RuntimeException('QUIQQER database is not available: ' . $Exception->getMessage(), 0, $Exception);
        }
    }

    private static function getAvailableProjectName(): string
    {
        $existingProjects = [];

        try {
            $existingProjects = array_keys(Manager::getConfig()->toArray());
        } catch (Throwable) {
        }

        if (!in_array(self::BASE_PROJECT_NAME, $existingProjects, true)) {
            return self::BASE_PROJECT_NAME;
        }

        for ($i = 1; $i < 100; $i++) {
            $projectName = self::BASE_PROJECT_NAME . '_' . $i;

            if (!in_array($projectName, $existingProjects, true)) {
                return $projectName;
            }
        }

        return self::BASE_PROJECT_NAME . '_' . substr(md5((string)microtime(true)), 0, 8);
    }

    public static function runAsSystemUser(callable $Callback): mixed
    {
        return self::withSystemUser($Callback);
    }

    private static function withSystemUser(callable $Callback): mixed
    {
        $Users = QUI::getUsers();
        $SystemUser = $Users->getSystemUser();
        $SessionProperty = new ReflectionProperty($Users, 'Session');
        $SessionProperty->setAccessible(true);
        $PreviousSessionUser = $SessionProperty->getValue($Users);

        $PermissionUserProperty = new ReflectionProperty(Permission::class, 'User');
        $PermissionUserProperty->setAccessible(true);
        $PreviousPermissionUser = $PermissionUserProperty->getValue();

        $SessionProperty->setValue($Users, $SystemUser);
        $PermissionUserProperty->setValue(null, $SystemUser);

        try {
            return $Callback();
        } finally {
            $SessionProperty->setValue($Users, $PreviousSessionUser);
            $PermissionUserProperty->setValue(null, $PreviousPermissionUser);
        }
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
        try {
            $Config = Manager::getConfig();
            $Config->del($projectName);
            $Config->save();
        } catch (Throwable) {
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
            if (!is_dir($directory)) {
                continue;
            }

            QUI::getTemp()->moveToTemp($directory);
        }
    }
}
