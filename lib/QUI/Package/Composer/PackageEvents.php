<?php

/**
 * This file contains \QUI\Package\Composer\PackageEvents
 */

namespace QUI\Package;

use QUI;
use Composer\Script\Event;
use Composer\Installer\PackageEvent;

class PackageEvents
{
    /**
     * Package Events
     */


    /**
     * Occurs before a package is installed.
     *
     * @param Event $Event
     */
    public static function prePackageInstall(Event $Event)
    {

    }

    /**
     * occurs after a package has been installed.
     *
     * @param Event $Event
     */
    public static function postPackageInstall(Event $Event)
    {
        QUI\System\Log::writeRecursive($Event);
    }

    /**
     * occurs before a package is updated.
     *
     * @param Event $Event
     */
    public static function prePackageUpdate(Event $Event)
    {

    }

    /**
     * occurs after a package has been updated.
     *
     * @param Event $Event
     */
    public function postPackageUpdate(Event $Event)
    {

    }

    /**
     * occurs before a package is uninstalled.
     *
     * @param Event $Event
     */
    public function prePackageUninstall(Event $Event)
    {

    }

    /**
     * occurs after a package has been uninstalled.
     *
     * @param Event $Event
     */
    public function postPackageUninstall(Event $Event)
    {

    }
}
