<?php

/**
 * This file contains \QUI\Package\Composer\PackageEvents
 */

namespace QUI\Package\Composer;

use Composer\Installer\PackageEvent;

use function dirname;
use function method_exists;
use function shell_exec;

use const PHP_BINARY;

class PackageEvents
{
    /**
     * Occurs before a package is installed.
     */
    public static function prePackageInstall(PackageEvent $Event)
    {
    }

    /**
     * occurs after a package has been installed.
     */
    public static function postPackageInstall(PackageEvent $Event): void
    {
        $Operation = $Event->getOperation();

        if (!method_exists($Operation, 'getPackage')) {
            return;
        }

        $TargetPackage = $Operation->getPackage();
        $packageName = $TargetPackage->getName();

        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        shell_exec("$phpPath $dir/postPackageInstall.php $packageName");
    }

    /**
     * occurs before a package is updated.
     */
    public static function prePackageUpdate(PackageEvent $Event)
    {
    }

    /**
     * occurs after a package has been updated.
     */
    public static function postPackageUpdate(PackageEvent $Event): void
    {
        $Operation = $Event->getOperation();

        if (!method_exists($Operation, 'getPackage')) {
            return;
        }

        $TargetPackage = $Operation->getPackage();
        $packageName = $TargetPackage->getName();

        // script
        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        shell_exec("$phpPath $dir/postPackageUpdate.php $packageName");
    }

    /**
     * occurs before a package is uninstalled.
     */
    public static function prePackageUninstall(PackageEvent $Event): void
    {
        $Operation = $Event->getOperation();

        if (!method_exists($Operation, 'getPackage')) {
            return;
        }

        $TargetPackage = $Operation->getPackage();
        $packageName = $TargetPackage->getName();

        // script
        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        shell_exec("$phpPath $dir/prePackageUninstall.php $packageName");
    }

    /**
     * occurs after a package has been uninstalled.
     */
    public static function postPackageUninstall(PackageEvent $Event): void
    {
        $Operation = $Event->getOperation();

        if (!method_exists($Operation, 'getPackage')) {
            return;
        }

        $TargetPackage = $Operation->getPackage();
        $packageName = $TargetPackage->getName();

        // script
        $phpPath = PHP_BINARY;
        $dir = dirname(__FILE__);

        shell_exec("$phpPath $dir/postPackageUninstall.php $packageName");
    }
}
