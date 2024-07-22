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
use function dirname;
use function parse_ini_file;
use function php_sapi_name;
use function str_replace;

use const CMS_DIR;
use const LIB_DIR;
use const OPT_DIR;
use const URL_DIR;
use const USR_DIR;
use const VAR_DIR;

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
        self::loadPaths($Event);

        foreach (self::$packages as $package) {
            try {
                $Package = QUI::getPackage($package);
                $Package->setup();
            } catch (QUI\Exception) {
            }
        }

        // project setups, because of new project default configs
        QUI::$PackageManager = null; // to get a new package manager instance

        $Projects = QUI::getProjectManager();
        $projects = $Projects->getProjects(true);

        foreach ($projects as $Project) {
            $Project->setup([
                'executePackagesSetup' => false
            ]);
        }

        // @todo system setup, user groups, events and so on
    }

    /**
     * Called before every composer command.
     * Using the commands require or remove causes cache inconsistencies.
     * Therefore, we tell the user how to prevent this.
     */
    public static function preCommandRun(PreCommandRunEvent $Event): void
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
        echo "Using the '$command' command might cause cache inconsistencies." . PHP_EOL;
        echo "If the QUIQQER menu bar disappears, clear the cache." . PHP_EOL;
        echo 'You should edit the composer.json directly and then execute a composer update.' . PHP_EOL;
        echo PHP_EOL;
    }

    /**
     * @throws Exception
     */
    protected static function loadQUIQQER(Event $Event): void
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

    /**
     * Loads all quiqqer paths into the current running instance
     */
    protected static function loadPaths(Event $Event): void
    {
        $Composer = $Event->getComposer();
        $config = $Composer->getConfig()->all();

        if (!defined('CMS_DIR')) {
            define('CMS_DIR', $config['config']['quiqqer-dir']);
        }

        if (!defined('ETC_DIR')) {
            define('ETC_DIR', $config['config']['quiqqer-dir'] . 'etc/');
        }

        // load the main configuration
        $config = parse_ini_file(ETC_DIR . 'conf.ini.php', true);
        $var_dir = $config['globals']['var_dir'];
        $lib_dir = dirname(__FILE__, 4) . '/';

        if (!defined('LIB_DIR')) {
            define('LIB_DIR', $lib_dir);
        }

        if (!defined('VAR_DIR')) {
            define('VAR_DIR', $var_dir);
        }

        if (!defined('BIN_DIR')) {
            define('BIN_DIR', dirname(LIB_DIR) . '/bin/');
        }

        if (!defined('USR_DIR')) {
            define('USR_DIR', $config['globals']['usr_dir']);
        }

        if (!defined('SYS_DIR')) {
            define('SYS_DIR', dirname(LIB_DIR) . '/admin/');
        }

        if (!defined('OPT_DIR')) {
            define('OPT_DIR', $config['globals']['opt_dir']);
        }

        if (!defined('URL_DIR')) {
            define('URL_DIR', $config['globals']['url_dir']);
        }

        if (!defined('URL_LIB_DIR')) {
            define('URL_LIB_DIR', $config['globals']['url_lib_dir']);
        }

        if (!defined('URL_BIN_DIR')) {
            define('URL_BIN_DIR', $config['globals']['url_bin_dir']);
        }

        if (!defined('URL_SYS_DIR')) {
            define('URL_SYS_DIR', $config['globals']['url_sys_dir']);
        }

        if (!defined('URL_USR_DIR')) {
            define('URL_USR_DIR', URL_DIR . str_replace(CMS_DIR, '', USR_DIR));
        }

        if (!defined('URL_OPT_DIR')) {
            define('URL_OPT_DIR', URL_DIR . str_replace(CMS_DIR, '', OPT_DIR));
        }

        if (!defined('URL_VAR_DIR')) {
            define('URL_VAR_DIR', URL_DIR . str_replace(CMS_DIR, '', VAR_DIR));
        }

        if (!defined('QUI_DB_PRFX')) {
            if (!empty($config['db']['prfx'])) {
                define('QUI_DB_PRFX', $config['db']['prfx']);
            } else {
                define('QUI_DB_PRFX', '');
            }
        }
    }
}
