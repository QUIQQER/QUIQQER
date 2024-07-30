<?php

/**
 * This file contains \QUI\Package\Composer\CommandEvents
 */

namespace QUI\Package\Composer;

use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;

use function array_unique;
use function dirname;
use function implode;
use function system;

use const PHP_BINARY;

class CommandEvents
{
    protected static array $install = [];
    protected static array $uninstall = [];
    protected static array $update = [];

    /**
     * Registered a package which has changed
     *
     * @param $packageName
     */
    public static function registerPackageUninstall($packageName): void
    {
        self::$uninstall[] = $packageName;
        self::$uninstall = array_unique(self::$uninstall);
    }

    public static function registerPackageInstall($packageName): void
    {
        self::$install[] = $packageName;
        self::$install = array_unique(self::$install);
    }

    public static function registerPackageUpdate($packageName): void
    {
        self::$update[] = $packageName;
        self::$update = array_unique(self::$update);
    }

    /**
     * occurs before the update command is executed,
     * or before install command is executed without a lock file present.
     */
    public static function preUpdate(Event $Event): void
    {
    }

    /**
     * occurs after the update command has been executed,
     * or after install command has been executed without a lock file present.
     */
    public static function postUpdate(Event $Event): void
    {
        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        if (!empty(self::$install)) {
            system("$phpPath $dir/postUpdateInstall.php " . implode(',', self::$install));
        }

        if (!empty(self::$update)) {
            system("$phpPath $dir/postUpdateUpdate.php " . implode(',', self::$update));
        }

        if (!empty(self::$uninstall)) {
            system("$phpPath $dir/postUpdateUninstall.php " . implode(',', self::$uninstall));
        }

        // execute complete setup
        system("$phpPath $dir/postUpdateSetup.php");
    }

    /**
     * occurs after the install command has been executed,
     */
    public static function postInstall(Event $Event): void
    {
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
