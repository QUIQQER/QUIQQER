<?php

namespace QUI\System\Update;

use function basename;
use function explode;
use function getcwd;
use function ini_get;
use function is_executable;
use function rtrim;
use function str_contains;
use function str_starts_with;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PATH_SEPARATOR;
use const PHP_BINDIR;
use const PHP_BINARY;
use const PHP_MAJOR_VERSION;
use const PHP_MINOR_VERSION;
use const URL_OPT_DIR;
use const VAR_DIR;

class RunLauncherFactory
{
    public static function createDefault(int $ttl = 3600): RunLauncher
    {
        return self::create(
            VAR_DIR,
            URL_OPT_DIR . 'quiqqer/core/bin/update-run.php',
            self::resolveCliPhpBinary(PHP_BINARY),
            $ttl
        );
    }

    public static function create(string $varDir, string $webRunnerUrl, string $phpBinary, int $ttl = 3600): RunLauncher
    {
        return new RunLauncher(
            new RunRepository(rtrim($varDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'update/runs/', $ttl),
            $webRunnerUrl,
            $phpBinary
        );
    }

    public static function resolveCliPhpBinary(string $binary): string
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
            if (!self::isAllowedByOpenBaseDir($candidate)) {
                continue;
            }

            if (@is_executable($candidate)) {
                return $candidate;
            }
        }

        return 'php';
    }

    private static function isAllowedByOpenBaseDir(string $path): bool
    {
        $openBaseDir = (string)ini_get('open_basedir');

        if ($openBaseDir === '' || $path === '' || $path[0] !== DIRECTORY_SEPARATOR) {
            return true;
        }

        foreach (explode(PATH_SEPARATOR, $openBaseDir) as $allowedPath) {
            $allowedPath = trim($allowedPath);

            if ($allowedPath === '') {
                continue;
            }

            if ($allowedPath === '.') {
                $allowedPath = (string)getcwd();
            }

            $allowedPath = rtrim($allowedPath, DIRECTORY_SEPARATOR);

            if ($allowedPath === '') {
                $allowedPath = DIRECTORY_SEPARATOR;
            }

            if ($path === $allowedPath || str_starts_with($path, $allowedPath . DIRECTORY_SEPARATOR)) {
                return true;
            }
        }

        return false;
    }
}
