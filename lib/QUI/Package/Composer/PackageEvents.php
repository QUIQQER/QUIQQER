<?php

/**
 * This file contains \QUI\Package\Composer\PackageEvents
 */

namespace QUI\Package\Composer;

use QUI;
use Composer\Installer\PackageEvent;

/**
 * Class PackageEvents
 * @package QUI\Package
 */
class PackageEvents
{
    /**
     * Occurs before a package is installed.
     *
     * @param PackageEvent $Event
     */
    public static function prePackageInstall(PackageEvent $Event)
    {
    }

    /**
     * occurs after a package has been installed.
     *
     * @param PackageEvent $Event
     */
    public static function postPackageInstall(PackageEvent $Event)
    {
        self::loadQUIQQER($Event);

//        $packageName = $Event->getOperation()->getPackage();

        QUI\System\Log::writeRecursive('install');
        QUI\System\Log::writeRecursive($Event);
    }

    /**
     * occurs before a package is updated.
     *
     * @param PackageEvent $Event
     */
    public static function prePackageUpdate(PackageEvent $Event)
    {
    }

    /**
     * occurs after a package has been updated.
     *
     * @param PackageEvent $Event
     */
    public static function postPackageUpdate(PackageEvent $Event)
    {
        self::loadQUIQQER($Event);
//        $packageName = $Event->getOperation()->getPackage();

        QUI\System\Log::writeRecursive('update');
        QUI\System\Log::writeRecursive($Event);
    }

    /**
     * occurs before a package is uninstalled.
     *
     * @param PackageEvent $Event
     */
    public static function prePackageUninstall(PackageEvent $Event)
    {
    }

    /**
     * occurs after a package has been uninstalled.
     *
     * @param PackageEvent $Event
     */
    public static function postPackageUninstall(PackageEvent $Event)
    {
    }

    /**
     * @param PackageEvent $Event
     */
    protected static function loadQUIQQER(PackageEvent $Event)
    {
        $Composer = $Event->getComposer();

        echo $Composer->getConfig()->get('quiqqer-dir');
    }
}
