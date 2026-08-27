<?php

namespace QUI\MCP\Project;

use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Projects\Manager;

abstract class AbstractProjectLifecycleTool extends AbstractTool
{
    protected static function checkProjectAdministration(): void
    {
        self::checkCorePermission();
        Permission::checkAdminUser(Server::getRequestUser());
    }

    protected static function checkProjectSuperUser(): void
    {
        self::checkCorePermission();
        Permission::checkSU(Server::getRequestUser());
    }

    protected static function requireConfirmation(bool $confirm): void
    {
        if (!$confirm) {
            throw new QUI\Exception('Project deletion requires confirm=true.');
        }
    }

    /**
     * @param array<array-key, mixed> $languages
     * @return array<int, string>
     */
    protected static function normalizeLanguages(string $defaultLanguage, array $languages): array
    {
        $availableLanguages = array_map(
            static fn(mixed $language): string => strtolower(trim((string)$language)),
            QUI::availableLanguages()
        );
        $defaultLanguage = strtolower(trim($defaultLanguage));

        if (!preg_match('/^[a-z]{2}$/', $defaultLanguage)) {
            throw new QUI\Exception('The default project language must be a two-letter language code.');
        }

        if (!in_array($defaultLanguage, $availableLanguages, true)) {
            throw new QUI\Exception('The default project language is not installed: ' . $defaultLanguage);
        }

        $result = [$defaultLanguage => $defaultLanguage];

        foreach ($languages as $language) {
            if (!is_string($language)) {
                throw new QUI\Exception('Every project language must be a string.');
            }

            $language = strtolower(trim($language));

            if (!preg_match('/^[a-z]{2}$/', $language)) {
                throw new QUI\Exception('Every project language must be a two-letter language code.');
            }

            if (!in_array($language, $availableLanguages, true)) {
                throw new QUI\Exception('Project language is not installed: ' . $language);
            }

            $result[$language] = $language;
        }

        return array_values($result);
    }

    protected static function validateTemplate(string $template): string
    {
        $template = trim($template);

        if ($template === '') {
            return '';
        }

        foreach (
            QUI::getPackageManager()->searchInstalledPackages([
                'type' => 'quiqqer-template'
            ]) as $package
        ) {
            if (($package['name'] ?? null) === $template) {
                return $template;
            }
        }

        throw new QUI\Exception('The selected package is not an installed QUIQQER template.');
    }

    protected static function assertProjectNameAvailable(string $name): void
    {
        QUI\Utils\Project::validateProjectName($name);

        if (isset(Manager::getConfig()->toArray()[$name])) {
            throw new QUI\Exception('A project with this name already exists: ' . $name);
        }
    }

    protected static function resetProjectManager(): void
    {
        Manager::cleanup();
        Manager::$Config = null;
        Manager::$Standard = null;
        unset(QUI::$Configs['etc/projects.ini'], QUI::$Configs['etc/projects.ini.php']);
    }

    protected static function renamePermissionReferences(string $oldName, string $newName): void
    {
        $Connection = QUI::getDataBaseConnection();
        $SchemaManager = QUI::getSchemaManager();

        foreach (self::getProjectPermissionTables() as $table) {
            if (!$SchemaManager->tablesExist([$table])) {
                continue;
            }

            $Connection->update($table, ['project' => $newName], ['project' => $oldName]);
        }
    }

    protected static function deletePermissionReferences(string $project): void
    {
        $Connection = QUI::getDataBaseConnection();
        $SchemaManager = QUI::getSchemaManager();

        foreach (self::getProjectPermissionTables() as $table) {
            if (!$SchemaManager->tablesExist([$table])) {
                continue;
            }

            $Connection->delete($table, ['project' => $project]);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function getProjectPermissionTables(): array
    {
        $table = PermissionManager::table();

        return [
            $table . '2projects',
            $table . '2sites',
            $table . '2media'
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function getConfirmationSchema(): array
    {
        return [
            'type' => 'boolean',
            'const' => true,
            'description' => 'Must be true to confirm irreversible project deletion.'
        ];
    }
}
