<?php

namespace QUI\System\Update;

use function basename;
use function is_executable;
use function str_contains;

use const PHP_BINDIR;
use const PHP_BINARY;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const URL_VAR_DIR;
use const VAR_DIR;

class RunLauncherFactory
{
    public static function createDefault(int $ttl = 3600): RunLauncher
    {
        return self::create(VAR_DIR, URL_VAR_DIR, self::resolveCliPhpBinary(PHP_BINARY), $ttl);
    }

    public static function create(string $varDir, string $urlVarDir, string $phpBinary, int $ttl = 3600): RunLauncher
    {
        return new RunLauncher(
            new RunRepository(rtrim($varDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'update/runs/', $ttl),
            rtrim($urlVarDir, '/') . '/update/runs/',
            $phpBinary
        );
    }

    private static function resolveCliPhpBinary(string $binary): string
    {
        $binaryName = basename($binary);

        if (
            !str_contains($binaryName, 'php-fpm')
            && !str_contains($binaryName, 'php-cgi')
            && !str_contains($binaryName, 'fpm')
        ) {
            return $binary;
        }

        $versionedBinary = 'php' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        $candidates = [
            PHP_BINDIR . DIRECTORY_SEPARATOR . $versionedBinary,
            PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
            '/usr/bin/' . $versionedBinary,
            '/usr/local/bin/' . $versionedBinary,
            '/usr/bin/php',
            '/usr/local/bin/php'
        ];

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }
}
