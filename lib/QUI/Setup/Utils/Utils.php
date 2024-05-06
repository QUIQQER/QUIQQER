<?php

namespace QUI\Setup\Utils;

use QUI\Utils\System;

/**
 * Class Utils
 *
 * Workaround class for the main setup stuff
 */
class Utils
{
    /**
     * Gets all available languages
     *
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        return ['de', 'en'];
    }

    /**
     * Makes sure , that the path ends with a trailing slash.
     *
     * @param $path - Raw Path
     *
     * @return string - Path with trailing slash.
     */
    public static function normalizePath($path): string
    {
        return rtrim(trim($path), '/') . '/';
    }

    /**
     * Checks if a directory is empty.
     *
     * @param $dir - Path to the directory.
     *
     * @return bool|null - Null, if an error occured. True if dir is empty, false if it is not.
     */
    public static function isDirEmpty($dir): ?bool
    {
        if (!is_dir($dir) || !is_readable($dir)) {
            return null;
        }

        $dirHandle = opendir($dir);

        while (($entry = readdir($dirHandle)) !== false) {
            if ($entry != '.' && $entry != '..') {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculates the MD5 sum of the given directory
     *
     * @param $dir
     *
     * @return bool|string
     */
    public static function getDirMD5($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }

        $fileHashes = [];
        $directory = dir($dir);

        while (($entry = $directory->read()) !== false) {
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            if (is_dir($dir . '/' . $entry)) {
                $fileHashes[] = self::getDirMD5($dir . '/' . $entry);
            } else {
                $fileHashes[] = md5($dir . '/' . $entry);
            }
        }

        $directory->close();

        return md5(implode('', $fileHashes));
    }

    /**
     * Sanitizes the given projectname
     *
     * @param $name
     *
     * @return string
     */
    public static function sanitizeProjectName($name): string
    {
        $forbiddenCharacters = [
            '-',
            '.',
            ',',
            ':',
            ';',
            '#',
            '`',
            '!',
            '§',
            '$',
            '%',
            '&',
            '/',
            '?',
            '<',
            '>',
            '=',
            '\'',
            '"',
            ' '
        ];

        $name = str_replace($forbiddenCharacters, '', $name);

        return trim($name);
    }

    /**
     * Detects the installed webservers
     * Returns a bitmask of webserver combinations
     * Apache 2.2 = 1
     * Apache 2.4 = 2
     * Nginx = 4
     *
     * Apache2.4 + Nginx = 6
     *
     * @return int
     */
    public static function detectWebserver(): int
    {
        $apache24 = false;
        $apache22 = false;
        $nginx = false;

        ##############
        #   Apache   #
        ##############

        # With shell access
        if (System::isShellFunctionEnabled('shell_exec')) {
            $version = shell_exec('apache2 -v 2> /dev/null');
            $regex = "/Apache\\/([0-9\\.]*)/i";
            $res = preg_match($regex, $version, $matches);
            if ($res && isset($matches[1])) {
                $version = $matches[1];

                $versionParts = explode('.', $version);

                if ($versionParts[1] <= 2) {
                    $apache22 = true;
                }

                if ($versionParts[1] >= 3) {
                    $apache24 = true;
                }
            }
        }

        # Attempt detection by apache2 module
        if (function_exists('apache_get_version')) {
            $version = apache_get_version();
            $regex = "/Apache\\/([0-9\\.]*)/i";
            $res = preg_match($regex, $version, $matches);

            if ($res && isset($matches[1])) {
                $version = $matches[1];
                $versionParts = explode('.', $version);

                if ($versionParts[1] <= 2) {
                    $apache22 = true;
                }

                if ($versionParts[1] >= 3) {
                    $apache24 = true;
                }
            }
        }

        ##############
        #   Nginx   #
        ##############

        # With shell access
        if (System::isShellFunctionEnabled('shell_exec')) {
            $version = shell_exec('nginx -v 2>&1 ');
            $regex = '~nginx/([0-9]+\.[0-9]+\.[0-9])+~i';
            $res = preg_match($regex, $version, $matches);
            if ($res && isset($matches[1])) {
                $nginx = true;
            }
        }

        $result = 0;

        $result = $apache22 ? $result + 1 : $result;
        $result = $apache24 ? $result + 2 : $result;

        return $nginx ? $result + 4 : $result;
    }

    /**
     * @param $templateName
     * @param $version
     * @return bool
     */
    public static function templateSupportsDemoData($templateName, $version): bool
    {
        $packagesJson = file_get_contents('https://update.quiqqer.com/packages.json');
        $packages = json_decode($packagesJson, true);
        $packages = $packages['packages'];

        if (!isset($packages[$templateName][$version])) {
            return false;
        }

        $templateData = $packages[$templateName][$version];

        if (!isset($templateData['extra']['quiqqer']['demodata'])) {
            return false;
        }

        return $templateData['extra']['quiqqer']['demodata'];
    }
}
