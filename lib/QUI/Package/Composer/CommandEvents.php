<?php

/**
 * This file contains \QUI\Package\Composer\CommandEvents
 */

namespace QUI\Package\Composer;

use QUI;
use Composer\Script\Event;

/**
 * Class CommandEvents
 *
 * @package QUI\Package
 */
class CommandEvents
{
    /**
     * @var array
     */
    protected static $packages = array();

    /**
     * Registered a package which has changed
     *
     * @param $packageName
     */
    public static function registerPackageChange($packageName)
    {
        self::$packages[] = $packageName;
        self::$packages   = array_unique(self::$packages);
    }

    /**
     * occurs before the update command is executed,
     * or before the install command is executed without a lock file present.
     *
     * @param Event $Event
     */
    public static function preUpdate(Event $Event)
    {
        self::$packages = array();
    }

    /**
     * occurs after the update command has been executed,
     * or after the install command has been executed without a lock file present.
     *
     * @param Event $Event
     */
    public static function postUpdate(Event $Event)
    {
        foreach (self::$packages as $package) {
            try {
                $Package = QUI::getPackage($package);
                $Package->setup();
            } catch (QUI\Exception $Package) {
            }
        }
    }

    /**
     * @param Event $Event
     */
    protected static function loadQUIQQER(Event $Event)
    {
        $Composer = $Event->getComposer();
        $config   = $Composer->getConfig()->all();

        if (!defined('CMS_DIR')) {
            define('CMS_DIR', $config['config']['quiqqer-dir']);
        }

        if (!defined('ETC_DIR')) {
            define('ETC_DIR', $config['config']['quiqqer-dir'] . 'etc/');
        }

        QUI::load();
    }
}
