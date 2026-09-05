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
    private static ?int $createdProjectProcessId = null;
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
        if (self::$createdProjectName === null || self::$createdProjectProcessId !== getmypid()) {
            return;
        }

        $projectName = self::$createdProjectName;

        try {
            self::withSystemUser(static function () use ($projectName): void {
                QUI\System\TestCleanup::cleanupProject($projectName);
            });
        } catch (Throwable) {
            // Cleanup must not hide the actual PHPUnit result.
        } finally {
            self::$projectName = null;
            self::$createdProjectName = null;
            self::$createdProjectProcessId = null;
        }
    }

    private static function createProject(): void
    {
        self::registerCleanup();
        self::skipIfDatabaseIsUnavailable();

        $projectName = self::getAvailableProjectName();
        self::$createdProjectName = $projectName;
        self::$createdProjectProcessId = (int)getmypid();

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

        if (
            !in_array(self::BASE_PROJECT_NAME, $existingProjects, true)
            && !self::projectLocaleFilesExist(self::BASE_PROJECT_NAME)
            && QUI\System\TestCleanup::claimProject(self::BASE_PROJECT_NAME)
        ) {
            return self::BASE_PROJECT_NAME;
        }

        for ($i = 1; $i < 100; $i++) {
            $projectName = self::BASE_PROJECT_NAME . '_' . $i;

            if (
                !in_array($projectName, $existingProjects, true)
                && !self::projectLocaleFilesExist($projectName)
                && QUI\System\TestCleanup::claimProject($projectName)
            ) {
                return $projectName;
            }
        }

        do {
            $projectName = self::BASE_PROJECT_NAME . '_' . bin2hex(random_bytes(8));
        } while (!QUI\System\TestCleanup::claimProject($projectName));

        return $projectName;
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

    private static function projectLocaleFilesExist(string $projectName): bool
    {
        $localeFiles = glob(VAR_DIR . 'locale/*/LC_MESSAGES/project_' . $projectName . '.ini.php');

        if (!empty($localeFiles)) {
            return true;
        }

        return is_dir(VAR_DIR . 'locale/bin/project/' . $projectName);
    }
}
