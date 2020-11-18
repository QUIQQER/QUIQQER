<?php

/**
 * This file contains \QUI\Package\Composer\PackageEvents
 */

namespace QUI\Package\Composer;

use Composer\DependencyResolver\Operation\UninstallOperation;
use QUI;

use Composer\Installer\PackageEvent;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UpdateOperation;

/**
 * Class PackageEvents
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

        /* @var $Operation InstallOperation */
        $Operation     = $Event->getOperation();
        $TargetPackage = $Operation->getPackage();
        $packageName   = $TargetPackage->getName();

        try {
            $Package = QUI::getPackage($packageName);
            $Package->install();

            CommandEvents::registerPackageChange($packageName);
        } catch (\Exception $Exception) {
            QUI\System\Log::write(
                $Exception->getMessage(),
                $Exception->getCode(),
                [
                    'method'  => 'QUI\Package\Composer\PackageEvents::postPackageInstall',
                    'package' => $packageName
                ]
            );
        }

        QUI\Cache\Manager::clearPackagesCache();
        QUI\Cache\Manager::clearSettingsCache();
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
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

        /* @var $Operation UpdateOperation */
        $Operation     = $Event->getOperation();
        $TargetPackage = $Operation->getTargetPackage();
        $packageName   = $TargetPackage->getName();

        try {
            $Package = QUI::getPackage($packageName);
            $Package->onUpdate();

            CommandEvents::registerPackageChange($packageName);
        } catch (\Exception $Exception) {
            QUI\System\Log::write(
                $Exception->getMessage(),
                $Exception->getCode(),
                [
                    'method'  => 'QUI\Package\Composer\PackageEvents::postPackageUpdate',
                    'package' => $packageName
                ]
            );
        }

        QUI\Cache\Manager::clearPackagesCache();
        QUI\Cache\Manager::clearSettingsCache();
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
    }

    /**
     * occurs before a package is uninstalled.
     *
     * @param PackageEvent $Event
     */
    public static function prePackageUninstall(PackageEvent $Event)
    {
        self::loadQUIQQER($Event);

        /* @var $Operation UninstallOperation */
        $Operation     = $Event->getOperation();
        $TargetPackage = $Operation->getPackage();
        $packageName   = $TargetPackage->getName();

        try {
            $Package = QUI::getPackage($packageName);
            $Package->uninstall();
        } catch (\Exception $Exception) {
            QUI\System\Log::write(
                $Exception->getMessage(),
                $Exception->getCode(),
                [
                    'method'  => 'QUI\Package\Composer\PackageEvents::postPackageUninstall',
                    'package' => $packageName
                ]
            );
        }

        QUI\Cache\Manager::clearPackagesCache();
        QUI\Cache\Manager::clearSettingsCache();
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
    }

    /**
     * occurs after a package has been uninstalled.
     *
     * @param PackageEvent $Event
     */
    public static function postPackageUninstall(PackageEvent $Event)
    {
        QUI\Cache\Manager::clearPackagesCache();
        QUI\Cache\Manager::clearSettingsCache();
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
    }

    /**
     * @param PackageEvent $Event
     */
    protected static function loadQUIQQER(PackageEvent $Event)
    {
        $Composer = $Event->getComposer();
        $config   = $Composer->getConfig()->all();

        if (!\defined('CMS_DIR')) {
            \define('CMS_DIR', $config['config']['quiqqer-dir']);
        }

        if (!\defined('ETC_DIR')) {
            \define('ETC_DIR', $config['config']['quiqqer-dir'].'etc/');
        }

        if (\php_sapi_name() === 'cli') {
            if (!defined('SYSTEM_INTERN')) {
                \define('SYSTEM_INTERN', true);
            }

            QUI\Permissions\Permission::setUser(
                QUI::getUsers()->getSystemUser()
            );
        }

        QUI::load();
    }
}
