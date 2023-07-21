<?php

/**
 * This file contains \QUI\Package\Composer\CommandEvents
 */

namespace QUI\Package\Composer;

use Composer\Plugin\PreCommandRunEvent;
use Composer\Script\Event;
use QUI;
use QUI\Exception;

use function array_unique;
use function define;
use function defined;
use function php_sapi_name;

/**
 * Class CommandEvents
 */
class CommandEvents
{
    /**
     * @var array
     */
    protected static array $packages = [];

    /**
     * Registered a package which has changed
     *
     * @param $packageName
     */
    public static function registerPackageChange($packageName)
    {
        self::$packages[] = $packageName;
        self::$packages = array_unique(self::$packages);
    }

    /**
     * occurs before the update command is executed,
     * or before install command is executed without a lock file present.
     *
     * @param Event $Event
     */
    public static function preUpdate(Event $Event)
    {
        self::$packages = [];
    }

    /**
     * occurs after the update command has been executed,
     * or after install command has been executed without a lock file present.
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

        // project setups, because of new project default configs
        $Projects = QUI::getProjectManager();
        $projects = $Projects->getProjects(true);

        foreach ($projects as $Project) {
            $Project->setup();
        }
        // @todo system setup, user groups, events and so on
    }

    /**
     * Called before every composer command.
     * Using the commands require or remove causes cache inconsistencies.
     * Therefore we tell the user how to prevent this.
     *
     * @param PreCommandRunEvent $Event
     */
    public static function preCommandRun(PreCommandRunEvent $Event)
    {
        if (php_sapi_name() !== 'cli') {
            return;
        }

        $command = $Event->getCommand();

        if ($command !== 'require' && $command !== "remove") {
            return;
        }

        echo PHP_EOL;

        echo 'WARNING:' . PHP_EOL;
        echo "Using the '{$command}' command might cause cache inconsistencies." . PHP_EOL;
        echo "If the QUIQQER menu bar disappears, clear the cache." . PHP_EOL;
        echo 'You should edit the composer.json directly and then execute a composer update.' . PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * @param Event $Event
     * @throws Exception
     */
    protected static function loadQUIQQER(Event $Event)
    {
        $Composer = $Event->getComposer();
        $config = $Composer->getConfig()->all();

        if (!defined('CMS_DIR')) {
            define('CMS_DIR', $config['config']['quiqqer-dir']);
        }

        if (!defined('ETC_DIR')) {
            define('ETC_DIR', $config['config']['quiqqer-dir'] . 'etc/');
        }

        if (php_sapi_name() === 'cli') {
            if (!defined('SYSTEM_INTERN')) {
                define('SYSTEM_INTERN', true);
            }

            QUI\Permissions\Permission::setUser(
                QUI::getUsers()->getSystemUser()
            );
        }

        QUI::load();
    }
}
