<?php

namespace QUI;

use Gettext\GettextTranslator;

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

    protected static ?GettextTranslator $GettextTranslator = null;

    /**
     * Set global locale group or
     *
     * @param string $lang
     * @param string $group
     * @param array $translations
     * @return void
     */
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
     * @return string|null
     */
    public static function get(string $lang, string $group, $value = false): ?string
    {
        if ($value === false) {
            if (isset(self::$languages[$lang][$group])) {
                return self::$languages[$lang][$group];
            }
        } else {
            if (isset(self::$languages[$lang][$group][$value])) {
                return self::$languages[$lang][$group][$value];
            }
        }

        return null;
    }

    /**
     * Check if a locale group
     *
     * @param string $lang
     * @param string $group
     *
     * @return bool
     */
    public static function isCached(string $lang, string $group): bool
    {
        return isset(self::$languages[$lang][$group]);
    }
}
