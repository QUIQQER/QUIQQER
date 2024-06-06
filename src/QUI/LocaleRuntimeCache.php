<?php

namespace QUI;

use function array_merge;

/**
 * Static class that holds global Locale data.
 */
class LocaleRuntimeCache
{
    /**
     * @var array Holds all locale vars by language and package
     */
    protected static array $languages = [];

    public static function set(string $lang, string $group, array $translations): void
    {
        if (!isset(self::$languages[$lang])) {
            self::$languages[$lang] = [];
        }

        if (!isset(self::$languages[$lang][$group])) {
            self::$languages[$lang][$group] = [];
        }

        self::$languages[$lang][$group] = array_merge(
            self::$languages[$lang][$group],
            $translations
        );
    }

    /**
     * Get cached translation.
     *
     * @param string $lang
     * @param string $group
     * @param string|bool $value - If the value is false, return the whole translation group
     *
     * @return string|null|array
     */
    public static function get(string $lang, string $group, $value = false)
    {
        if ($value === false) {
            if (isset(self::$languages[$lang][$group])) {
                return self::$languages[$lang][$group];
            }
        } elseif (isset(self::$languages[$lang][$group][$value])) {
            return self::$languages[$lang][$group][$value];
        }

        return null;
    }

    public static function isCached(string $lang, string $group): bool
    {
        return isset(self::$languages[$lang][$group]);
    }
}
