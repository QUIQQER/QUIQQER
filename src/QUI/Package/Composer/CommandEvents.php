<?php

/**
 * This file contains \QUI\Package\Composer\CommandEvents
 */

namespace QUI\Package\Composer;

use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;

use function array_unique;
use function dirname;

class CommandEvents
{
    protected static array $packages = [];

    /**
     * Registered a package which has changed
     *
     * @param $packageName
     */
    public static function registerPackageChange($packageName): void
    {
        self::$packages[] = $packageName;
        self::$packages = array_unique(self::$packages);
    }

    /**
     * occurs before the update command is executed,
     * or before install command is executed without a lock file present.
     */
    public static function preUpdate(Event $Event): void
    {
        self::$packages = [];
    }

    /**
     * occurs after the update command has been executed,
     * or after install command has been executed without a lock file present.
     */
    public static function postUpdate(Event $Event): void
    {
        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        shell_exec("$phpPath $dir/postUpdate.php");
    }

    /**
     * Called before every composer command.
     * Using the commands require or remove causes cache inconsistencies.
     * Therefore, we tell the user how to prevent this.
     */
    public static function preCommandRun(PreCommandRunEvent $Event): void
    {
    }
}
