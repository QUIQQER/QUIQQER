<?php

namespace QUI\System\Update;

use const PHP_BINARY;
use const URL_VAR_DIR;
use const VAR_DIR;

class RunLauncherFactory
{
    public static function createDefault(int $ttl = 3600): RunLauncher
    {
        return self::create(VAR_DIR, URL_VAR_DIR, PHP_BINARY, $ttl);
    }

    public static function create(string $varDir, string $urlVarDir, string $phpBinary, int $ttl = 3600): RunLauncher
    {
        return new RunLauncher(
            new RunRepository(rtrim($varDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'update/runs/', $ttl),
            rtrim($urlVarDir, '/') . '/update/runs/',
            $phpBinary
        );
    }
}
