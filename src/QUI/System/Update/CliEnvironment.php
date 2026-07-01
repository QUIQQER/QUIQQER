<?php

namespace QUI\System\Update;

use function escapeshellarg;
use function explode;
use function getenv;
use function implode;
use function in_array;

use const PATH_SEPARATOR;
use const PHP_OS_FAMILY;

class CliEnvironment
{
    public static function createShellPrefix(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return '';
        }

        $path = self::getPathWithDefaults();

        if ($path === '') {
            return '';
        }

        return 'PATH=' . escapeshellarg($path) . ' ';
    }

    private static function getPathWithDefaults(): string
    {
        $paths = [];
        $currentPath = getenv('PATH');

        if ($currentPath !== false && $currentPath !== '') {
            foreach (explode(PATH_SEPARATOR, $currentPath) as $path) {
                if ($path !== '' && !in_array($path, $paths, true)) {
                    $paths[] = $path;
                }
            }
        }

        foreach (self::getDefaultPathEntries() as $path) {
            if (!in_array($path, $paths, true)) {
                $paths[] = $path;
            }
        }

        return implode(PATH_SEPARATOR, $paths);
    }

    /**
     * @return array<int, string>
     */
    private static function getDefaultPathEntries(): array
    {
        if (PHP_OS_FAMILY === 'Darwin') {
            return [
                '/opt/homebrew/bin',
                '/usr/local/bin',
                '/usr/bin',
                '/bin',
                '/usr/sbin',
                '/sbin'
            ];
        }

        return [
            '/usr/local/sbin',
            '/usr/local/bin',
            '/usr/sbin',
            '/usr/bin',
            '/sbin',
            '/bin'
        ];
    }
}
