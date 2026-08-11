<?php

/**
 * This file contains the \QUI\MCP\Project\AbstractProjectSettingsTool
 */

namespace QUI\MCP\Project;

use QUI;
use QUI\AI\MCP\Server;
use QUI\MCP\AbstractTool;
use QUI\Permissions\Permission;
use QUI\Projects\Manager;
use QUI\Projects\Project;

abstract class AbstractProjectSettingsTool extends AbstractTool
{
    protected static function checkProjectSettingsPermission(Project $Project): void
    {
        Permission::checkProjectPermission(
            'quiqqer.projects.setconfig',
            $Project,
            Server::getRequestUser()
        );
    }

    /**
     * @return array<int, array{
     *     key: string,
     *     value: bool|float|int|string,
     *     default: bool|float|int|string,
     *     type: string,
     *     source: string
     * }>
     */
    protected static function getSettings(Project $Project, ?string $prefix = null): array
    {
        $settings = [];
        $definitions = Manager::getProjectConfigDefinitions($Project);
        $config = $Project->getConfig();

        if (!is_array($config)) {
            $config = [];
        }

        ksort($definitions);

        foreach ($definitions as $key => $definition) {
            if ($prefix !== null && $prefix !== '' && !str_starts_with($key, $prefix)) {
                continue;
            }

            $settings[] = self::buildSetting($key, $definition, $config);
        }

        return $settings;
    }

    /**
     * @return array{
     *     key: string,
     *     value: bool|float|int|string,
     *     default: bool|float|int|string,
     *     type: string,
     *     source: string
     * }
     *
     * @throws QUI\Exception
     */
    protected static function getSetting(Project $Project, string $key): array
    {
        $definitions = Manager::getProjectConfigDefinitions($Project);

        if (!array_key_exists($key, $definitions)) {
            throw new QUI\Exception('Unknown project setting "' . $key . '".');
        }

        $definition = $definitions[$key];
        $config = $Project->getConfig();

        if (!is_array($config)) {
            $config = [];
        }

        return self::buildSetting($key, $definition, $config);
    }

    /**
     * @param array<array-key, mixed> $settings
     * @return array{
     *     project: Project,
     *     settings: array<int, array{
     *         key: string,
     *         value: bool|float|int|string,
     *         previousValue: bool|float|int|string,
     *         changed: bool,
     *         default: bool|float|int|string,
     *         type: string,
     *         source: string
     *     }>
     * }
     *
     * @throws QUI\Exception
     * @throws \Exception
     */
    protected static function updateSettings(Project $Project, array $settings): array
    {
        if ($settings === []) {
            throw new QUI\Exception('At least one project setting must be provided.');
        }

        $definitions = Manager::getProjectConfigDefinitions($Project);
        $previousSettings = [];
        $validatedSettings = [];

        foreach ($settings as $key => $value) {
            if (!is_string($key) || !array_key_exists($key, $definitions)) {
                throw new QUI\Exception('Unknown project setting "' . $key . '".');
            }

            $type = self::normalizeType($definitions[$key]['type']);
            self::validateValue($key, $value, $type);
            $previousSettings[$key] = self::getSetting($Project, $key);
            $validatedSettings[$key] = $value;
        }

        Manager::setConfigForProject($Project->getName(), $validatedSettings);

        $Project = self::getProject($Project->getName());
        $updatedSettings = [];

        foreach (array_keys($validatedSettings) as $key) {
            $setting = self::getSetting($Project, $key);
            $previousValue = $previousSettings[$key]['value'];
            $setting['previousValue'] = $previousValue;
            $setting['changed'] = $previousValue !== $setting['value'];
            $updatedSettings[] = $setting;
        }

        return [
            'project' => $Project,
            'settings' => $updatedSettings
        ];
    }

    protected static function normalizeType(string $type): string
    {
        return match (strtolower(trim($type))) {
            'bool', 'boolean' => 'boolean',
            'int', 'integer' => 'integer',
            'float', 'double', 'number' => 'number',
            default => 'string'
        };
    }

    /**
     * @throws QUI\Exception
     */
    protected static function validateValue(string $key, mixed $value, string $type): void
    {
        $valid = match ($type) {
            'boolean' => is_bool($value),
            'integer' => is_int($value),
            'number' => is_int($value) || is_float($value),
            default => is_string($value)
        };

        if (!$valid) {
            throw new QUI\Exception(
                'Invalid value for project setting "' . $key . '": expected ' . $type . '.'
            );
        }
    }

    protected static function normalizeOutputValue(mixed $value, string $type): bool | float | int | string
    {
        return match ($type) {
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int)$value,
            'number' => is_int($value) ? $value : (float)$value,
            default => (string)$value
        };
    }

    /**
     * @param array{default: mixed, type: string, source: string} $definition
     * @param array<string, mixed> $config
     * @return array{
     *     key: string,
     *     value: bool|float|int|string,
     *     default: bool|float|int|string,
     *     type: string,
     *     source: string
     * }
     */
    private static function buildSetting(string $key, array $definition, array $config): array
    {
        $type = self::normalizeType($definition['type']);
        $value = array_key_exists($key, $config) ? $config[$key] : $definition['default'];

        return [
            'key' => $key,
            'value' => self::normalizeOutputValue($value, $type),
            'default' => self::normalizeOutputValue($definition['default'], $type),
            'type' => $type,
            'source' => $definition['source']
        ];
    }
}
