<?php

namespace QUI;

use QUI\Utils\Translation\GetText;
use function array_merge;
use function is_array;

/**
 * Static class that holds global Locale data.
 */
class LocaleRuntimeCache
{
    /**
     * @var array Holds all locale vars by language and package
     */
    protected static array $langs = [];

    /**
     * @var GetText[]
     */
    protected static array $getTextInstances = [];

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
        if (!isset(self::$langs[$lang])) {
            self::$langs[$lang] = [];
        }

        if (!isset(self::$langs[$lang][$group])) {
            self::$langs[$lang][$group] = [];
        }

        self::$langs[$lang][$group] = array_merge(
            self::$langs[$lang][$group],
            $translations
        );
    }

    /**
     * Set translation cache via GetText object.
     *
     * @param string $lang
     * @param string $group
     * @param GetText $GetText
     *
     * @return void
     */
    public static function setWithGetText(string $lang, string $group, GetText $GetText): void
    {
        self::$getTextInstances[$lang][$group] = $GetText;
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
        if (isset(self::$getTextInstances[$lang][$group])) {
            /** @var GetText $GetTextInstance */
            $GetTextInstance = self::$getTextInstances[$lang][$group];
            $str             = $GetTextInstance->get($value);

            if ($value != $str) {
                return $str;
            }
        }

        if ($value === false) {
            if (isset(self::$langs[$lang][$group])) {
                return self::$langs[$lang][$group];
            }
        } else {
            if (isset(self::$langs[$lang][$group][$value])) {
                return self::$langs[$lang][$group][$value];
            }
        }

        return null;
    }

    /**
     * Check if a locale group
     *
     * @param string $lang
     * @param string $group
     * @param bool $viaGetText (optional) - Check if it is cached via gettext
     * @return bool
     */
    public static function isCached(string $lang, string $group, bool $viaGetText = false): bool
    {
        if ($viaGetText) {
            return isset(self::$getTextInstances[$lang][$group]);
        }

        return isset(self::$langs[$lang][$group]);
    }
}
