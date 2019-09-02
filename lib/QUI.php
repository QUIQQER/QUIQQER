<?php

/**
 * This file contains QUI
 */

use \Symfony\Component\HttpFoundation\Cookie;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;

/**
 * The Main Object of the QUIQQER Management System
 *
 * @example
 * \QUI::conf();
 * \QUI::getDataBase();
 * \QUI::getPDO();
 * \QUI::getLocale();
 * and so on
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package quiqqer/quiqqer
 */
class QUI
{
    /**
     * FRONTEND FLAG
     */
    const FRONTEND = 1;

    /**
     * BACKEND FLAG
     */
    const BACKEND = 2;

    /**
     * SYSTEM (Console) FLAG
     */
    const SYSTEM = 3;

    /**
     * QUI Config, use \QUI::getConfig()
     *
     * @var \QUI\Config
     */
    public static $Conf = null;

    /**
     * QUI getDB object, use \QUI::getDB()
     *
     * @var \QUI\Utils\MyDB
     */
    public static $DataBase = null;

    /**
     * QUI getDataBase object, use \QUI::getDataBase();
     *
     * @var \QUI\Database\DB
     */
    public static $DataBase2 = null;

    /**
     * QUI Error Handler, use \QUI::getErrorHandler();
     *
     * @var \QUI\Exceptions\Handler
     */
    public static $ErrorHandler = null;

    /**
     * QUI vhosts, use \QUI::vhosts();
     *
     * @var array
     */
    public static $vhosts = null;

    /**
     * Timestamp of the last update
     *
     * @var integer
     */
    public static $last_up_date = null;

    /**
     * QUI Ajax
     *
     * @var \QUI\Ajax
     */
    public static $Ajax = null;

    /**
     * QUI GroupManager, use \QUI::getGroups()
     *
     * @var \QUI\Groups\Manager
     */
    public static $Groups = null;

    /**
     * QUI Message Handler, use \QUI::getMessageHandler()
     *
     * @var \QUI\Messages\Handler
     */
    public static $MessageHandler = null;

    /**
     * QUI Locale Object, use \QUI::getLocale()
     *
     * @var \QUI\Locale
     */
    public static $Locale = null;

    /**
     * QUI default Locale Object
     *
     * @var \QUI\Locale
     */
    protected static $SystemLocale = null;

    /**
     * QUI Mail Manager
     *
     * @var \QUI\Mail\Manager
     */
    public static $MailManager = null;

    /**
     * QUI Pluginmanager, use \QUI::getPlugins();
     *
     * @var \QUI\Plugins\Manager
     */
    public static $Plugins = null;

    /**
     * QUI Packagemanager, use \QUI::getPackageManager();
     *
     * @var \QUI\Package\Manager
     */
    public static $PackageManager = null;

    /**
     * QUI Projectmanager, use \QUI::getProjectManager();
     *
     * @var \QUI\Projects\Manager
     */
    public static $ProjectManager = null;

    /**
     * QUI Projectmanager, use \QUI::getProjectManager();
     *
     * @var Request
     */
    public static $Request = null;

    /**
     * Global Response Object
     *
     * @var Response
     */
    public static $Response = null;

    /**
     * QUI Rewrite Object, use \QUI::getRewrite();
     *
     * @var \QUI\Rewrite
     */
    public static $Rewrite = null;

    /**
     * QUI Rights Object, use \QUI::getRights();
     *
     * @var \QUI\Permissions\Manager
     */
    public static $Rights = null;

    /**
     * QUI Session Object, use \QUI::getSession();
     *
     * @var \QUI\Session
     */
    public static $Session = null;

    /**
     * QUI\Temp Object, use \QUI::getTemp();
     *
     * @var \QUI\Temp
     */
    public static $Temp = null;

    /**
     * QUI User Manager, use \QUI::getUsers();
     *
     * @var \QUI\Users\Manager
     */
    public static $Users = null;

    /**
     * internal config objects, array list of configs
     *
     * @var array
     */
    public static $Configs = [];

    /**
     * QUI global Events
     *
     * @var \QUI\Events\Manager
     */
    public static $Events = null;

    /**
     * Country Manager
     *
     * @var \QUI\Countries\Manager
     */
    public static $Countries = null;

    /**
     * Template Manager
     *
     * @var \QUI\Template
     */
    public static $Template = null;

    /**
     * Set all important pathes and load QUIQQER
     */
    public static function load()
    {
        // load the main configuration
        $config = \parse_ini_file(ETC_DIR.'conf.ini.php', true);

        /**
         * load the constants
         */

        if (!\defined('CMS_DIR')) {
            /**
             * CMS_DIR - Path to the quiqqer folder, where the whole system are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('CMS_DIR', $config['globals']['cms_dir']);
        }

        if (!\defined('DEBUG_MODE')) {
            /**
             * DEBUG_MODE - setting if debug mode is enabled or not
             *
             * @var boolean
             * @package com.pcsg.qui
             */
            \define("DEBUG_MODE", $config['globals']['debug_mode']);
        }

        if (!\defined('DEVELOPMENT')) {
            /**
             * DEVELOPMENT - setting if the system is in development mode or not
             *
             * @var boolean
             * @package com.pcsg.qui
             */
            \define("DEVELOPMENT", $config['globals']['development']);
        }

        $var_dir = $config['globals']['var_dir'];

        if (\file_exists($var_dir.'last_update')) {
            self::$last_up_date = \file_get_contents($var_dir.'last_update');
        } else {
            self::$last_up_date = \time();
        }

        $lib_dir = \dirname(__FILE__).'/';
        $var_dir = $config['globals']['var_dir'];

        // Define quiqqer path constants

        if (!\defined('LIB_DIR')) {
            /**
             * LIB_DIR - Path to the lib folder, where all the libraries are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('LIB_DIR', $lib_dir);
        }

        if (!\defined('VAR_DIR')) {
            /**
             * VAR_DIR - Path to the var folder,
             * where all the files are located on which the web server must have access
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('VAR_DIR', $var_dir);
        }

        if (!\defined('BIN_DIR')) {
            /**
             * BIN_DIR - Path to the bin folder, where all temp files are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('BIN_DIR', \dirname(LIB_DIR).'/bin/');
        }

        if (!\defined('USR_DIR')) {
            /**
             * USR_DIR - Path to the usr folder, where all projects are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('USR_DIR', $config['globals']['usr_dir']);
        }

        if (!\defined('SYS_DIR')) {
            /**
             * SYS_DIR - Path to the etc folder, where all the configurations are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('SYS_DIR', \dirname(LIB_DIR).'/admin/');
        }

        if (!\defined('OPT_DIR')) {
            /**
             * OPT_DIR - Path to the plugin folder, where all plugins are located
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('OPT_DIR', $config['globals']['opt_dir']);
        }

        if (!\defined('URL_DIR')) {
            /**
             * URL_DIR - path by which the system is accessible via the browser
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('URL_DIR', $config['globals']['url_dir']);
        }


        $Config     = new QUI\Config(ETC_DIR.'conf.ini.php');
        self::$Conf = $Config;

        if ($Config->getValue('globals', 'timezone')) {
            \date_default_timezone_set($Config->getValue('globals', 'timezone'));
        }


        if (!\defined('ERROR_BACKTRACE')) {
            /**
             * ERROR_BACKTRACE - configuration,
             * if a backtrace should write in the logs during a error
             *
             * @var string
             * @package com.pcsg.qui
             */
            \define('ERROR_BACKTRACE', $Config->get('error', 'backtrace'));
        }

        if (!\defined('QUI_DB_PRFX')) {
            /**
             * QUI_DB_PRFX - The DB Table Prefix
             *
             * @var string
             * @package com.pcsg.qui
             */

            $prfx = '';

            if ($Config->get('db', 'prfx')) {
                $prfx = $Config->get('db', 'prfx');
            }

            \define('QUI_DB_PRFX', $prfx);
        }

        // create the temp folder
        // @todo better do at the setup
        $folders = [
            // VAR
            VAR_DIR.'log/',
            VAR_DIR.'sessions/',
            VAR_DIR.'uid_sess/',
            VAR_DIR.'backup/',
            VAR_DIR.'lock/',

            // Cache - noch nötig?
            VAR_DIR.'locale/',
            VAR_DIR.'tmp/' // @todo temp
        ];

        foreach ($folders as $folder) {
            QUI\Utils\System\File::mkdir($folder);
        }


        if (!\defined('URL_LIB_DIR')) {
            \define('URL_LIB_DIR', QUI::conf('globals', 'url_lib_dir'));
        }

        if (!\defined('URL_BIN_DIR')) {
            \define('URL_BIN_DIR', QUI::conf('globals', 'url_bin_dir'));
        }

        if (!\defined('URL_SYS_DIR')) {
            \define('URL_SYS_DIR', QUI::conf('globals', 'url_sys_dir'));
        }

        if (!\defined('URL_USR_DIR')) {
            \define('URL_USR_DIR', URL_DIR.\str_replace(CMS_DIR, '', USR_DIR));
        }

        if (!\defined('URL_OPT_DIR')) {
            \define('URL_OPT_DIR', URL_DIR.\str_replace(CMS_DIR, '', OPT_DIR));
        }

        if (!\defined('URL_VAR_DIR')) {
            \define('URL_VAR_DIR', URL_DIR.\str_replace(CMS_DIR, '', VAR_DIR));
        }

        // bugfix: workround: Uncaught Error: Call to undefined function DusanKasan\Knapsack\append()
        if (!\function_exists('\DusanKasan\Knapsack\append')) {
            if (\file_exists(OPT_DIR.'dusank/knapsack/src/collection_functions.php')) {
                require_once OPT_DIR.'dusank/knapsack/src/collection_functions.php';
            }
        }


        // Load Packages
        self::getPackageManager();


        // mem peak - info mail at 80% usage
        self::getErrorHandler()->registerShutdown(function () {
            QUI\Utils\System\Debug::marker('END');

            // ram peak, if the ram usage is to high, than write and send a message
            $peak      = \memory_get_peak_usage();
            $mem_limit = QUI\Utils\System\File::getBytes(ini_get('memory_limit')) * 0.8;

            if ($peak > $mem_limit && $mem_limit > 0) {
                $limit = QUI\Utils\System\File::formatSize(
                    \memory_get_peak_usage()
                );

                if (!isset($_SERVER["HTTP_HOST"])) {
                    $_SERVER["HTTP_HOST"] = '';
                }

                if (!isset($_SERVER["REQUEST_URI"])) {
                    $_SERVER["REQUEST_URI"] = $_SERVER["SCRIPT_FILENAME"];
                }

                if (!isset($_SERVER["HTTP_REFERER"])) {
                    $_SERVER["HTTP_REFERER"] = '';
                }

                $message = "Peak usage: ".$limit."\n".
                           "memory_limit: ".\ini_get('memory_limit')."\n".
                           "URI: ".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]."\n".
                           "HTTP_REFERER: ".$_SERVER["HTTP_REFERER"];

                if (self::conf('mail', 'admin_mail')) {
                    self::getMailManager()->send(
                        self::conf('mail', 'admin_mail'),
                        'Memory limit reached at http://'.$_SERVER["HTTP_HOST"],
                        $message
                    );
                }

                QUI\System\Log::addAlert($message);
            }
        });


        // there are system changes?
        // then make a setup
        if ($Config->get('globals', 'system_changed')) {
            QUI\Setup::all();

            $Config->set('globals', 'system_changed', 0);
            $Config->save();
        }
    }

    /**
     * Starts the Setup
     */
    public static function setup()
    {
        QUI\Setup::all();
    }

    /**
     * Get a QUIQQER main configuration entry
     *
     * @param string $section
     * @param string|null $key (optional)
     *
     * @return mixed
     */
    public static function conf($section, $key = null)
    {
        if (self::$Conf === null) {
            self::$Conf = self::getConfig('etc/conf.ini.php');
        }

        return self::$Conf->get($section, $key);
    }

    /**
     * Returns all available languages
     *
     * @return array
     */
    public static function availableLanguages()
    {
        $langs = QUI\Translator::getAvailableLanguages();

        if (empty($langs)) {
            $langs = ['en'];
        }

        return $langs;
    }

    /**
     * Return the QUIQQER version
     *
     * @return string
     */
    public static function version()
    {
        return self::getPackageManager()->getVersion();
    }

    /**
     * Get registered vhosts
     *
     * @return array
     */
    public static function vhosts()
    {
        if (self::$vhosts !== null) {
            return self::$vhosts;
        }

        try {
            $vhosts       = self::getConfig('etc/vhosts.ini.php');
            self::$vhosts = $vhosts->toArray();
        } catch (\QUI\Exception $Exception) {
            self::$vhosts = [];
        }

        return self::$vhosts;
    }

    /**
     * Return the global ajax object
     *
     * @return \QUI\Ajax
     */
    public static function getAjax()
    {
        if (self::$Ajax === null) {
            self::$Ajax = new QUI\Ajax([
                'db_errors' => self::conf('error', 'mysql_ajax_errors_backend')
            ]);
        }

        return self::$Ajax;
    }

    /**
     * Return the tablename with the QUI Prefix
     *
     * @param string $table
     *
     * @return string
     */
    public static function getDBTableName($table)
    {
        return QUI_DB_PRFX.$table;
    }

    /**
     * Return the tablename with the QUI Prefix and table params
     *
     * @param string $table
     * @param \QUI\Projects\Project
     * @param boolean $lang - language in the table name? default = true
     *
     * @return string
     */
    public static function getDBProjectTableName(
        $table,
        \QUI\Projects\Project $Project,
        $lang = true
    ) {
        if ($lang === false) {
            return QUI_DB_PRFX.$Project->getName().'_'.$table;
        }

        return QUI_DB_PRFX.$Project->getName().'_'.$Project->getLang().'_'.$table;
    }

    /**
     * Returns a config object for a INI file
     * Starting from CMS_DIR
     *
     * @param string $file
     *
     * @return \QUI\Config
     * @throws \QUI\Exception
     *
     */
    public static function getConfig($file)
    {
        if (isset(self::$Configs[$file])) {
            return self::$Configs[$file];
        }

        $_file = CMS_DIR.$file;

        if (\substr($file, -4) !== '.php') {
            $_file .= '.php';
        }

        if (!isset(self::$Configs[$file])) {
            if (!\file_exists($_file) || \is_dir($_file)) {
                throw new \QUI\Exception(
                    'Error: Ini Datei: '.$_file.' existiert nicht.',
                    404
                );
            }

            self::$Configs[$file] = new \QUI\Config($_file);
        }

        return self::$Configs[$file];
    }

    /**
     * Returns the Country Manager
     *
     * @return \QUI\Countries\Manager
     */
    public static function getCountries()
    {
        if (self::$Countries === null) {
            self::$Countries = new \QUI\Countries\Manager();
        }

        return self::$Countries;
    }

    /**
     * Returns the Datebase Object (old version)
     *
     * @return \QUI\Utils\MyDB
     * @deprecated
     * use getDataBase and PDO or direct getPDO
     */
    public static function getDB()
    {
        if (self::$DataBase === null) {
            self::$DataBase = new \QUI\Utils\MyDB();
        }

        return self::$DataBase;
    }

    /**
     * Returns the Database object
     *
     * @return \QUI\Database\DB
     */
    public static function getDataBase()
    {
        if (self::$DataBase2 === null) {
            self::$DataBase2 = new \QUI\Database\DB([
                'driver'   => self::conf('db', 'driver'),
                'host'     => self::conf('db', 'host'),
                'user'     => self::conf('db', 'user'),
                'password' => self::conf('db', 'password'),
                'dbname'   => self::conf('db', 'database')
            ]);
        }

        return self::$DataBase2;
    }

    /**
     * Returns the globals Events object
     *
     * @return \QUI\Events\Manager
     */
    public static function getEvents()
    {
        if (self::$Events === null) {
            self::$Events = new \QUI\Events\Manager();
        }

        return self::$Events;
    }

    /**
     * Returns the PDO Database object
     *
     * @return \PDO
     */
    public static function getPDO()
    {
        return self::getDataBase()->getPDO();
    }

    /**
     * Returns a Project
     * It use the \QUI\Projects\Manager
     *
     * You can also use \QUI\Projects\Manager::getProject()
     *
     * @param string|array $project - Project name | array('name' => , 'lang' => , 'template' => )
     * @param string|boolean $lang - Project lang (optional)
     * @param string|boolean $template - Project template (optional)
     *
     * @return \QUI\Projects\Project
     * @throws QUI\Exception
     * @uses \QUI\Projects\Manager
     *
     */
    public static function getProject($project, $lang = false, $template = false)
    {
        if (\is_array($project)) {
            $lang     = false;
            $template = false;

            if (isset($project['lang'])) {
                $lang = $project['lang'];
            }

            if (isset($project['template'])) {
                $template = $project['template'];
            }

            if (isset($project['project'])) {
                $project = $project['project'];
            }
        }

        return \QUI\Projects\Manager::getProject($project, $lang, $template);
    }

    /**
     * Returns the ErrorHandler
     *
     * @return \QUI\Exceptions\Handler
     */
    public static function getErrorHandler()
    {
        if (self::$ErrorHandler === null) {
            require_once \dirname(__FILE__).'/QUI/Exceptions/Handler.php';

            self::$ErrorHandler = new \QUI\Exceptions\Handler();

            self::$ErrorHandler->setAttribute(
                'logdir',
                self::conf('globals', 'var_dir').'log/'
            );

            self::$ErrorHandler->setAttribute(
                'backtrace',
                self::conf('error', 'backtrace')
            );
        }

        return self::$ErrorHandler;
    }

    /**
     * Returns the group manager
     *
     * @return \QUI\Groups\Manager
     */
    public static function getGroups()
    {
        if (self::$Groups === null) {
            self::$Groups = new \QUI\Groups\Manager();
        }

        return self::$Groups;
    }

    /**
     * Returns the QUIQQER message handler object
     *
     * @return \QUI\Messages\Handler
     */
    public static function getMessagesHandler()
    {
        if (self::$MessageHandler === null) {
            self::$MessageHandler = new \QUI\Messages\Handler();
        }

        return self::$MessageHandler;
    }

    /**
     * Returns the main locale object
     *
     * @return \QUI\Locale
     */
    public static function getLocale()
    {
        if (self::$Locale === null) {
            self::$Locale = new \QUI\Locale();

            if (isset($_REQUEST['lang']) && \strlen($_REQUEST['lang']) === 2) {
                self::$Locale->setCurrent($_REQUEST['lang']);
            } else {
                $language = self::conf('globals', 'standardLanguage');

                if (!empty($language)) {
                    self::$Locale->setCurrent($language);
                }
            }
        }

        return self::$Locale;
    }

    /**
     * Return the QUIQQER default language locale
     *
     * @return \QUI\Locale
     */
    public static function getSystemLocale()
    {
        if (self::$SystemLocale !== null) {
            return self::$SystemLocale;
        }

        self::$Locale = new \QUI\Locale();
        $language     = self::conf('globals', 'standardLanguage');

        if (!empty($language)) {
            self::$Locale->setCurrent($language);
        }

        return self::$Locale;
    }

    /**
     * Return the mail manager
     *
     * @return \QUI\Mail\Manager
     */
    public static function getMailManager()
    {
        if (self::$MailManager === null) {
            self::$MailManager = new \QUI\Mail\Manager();
        }

        return self::$MailManager;
    }

    /**
     * Returns the package manager
     *
     * @return \QUI\Package\Manager
     */
    public static function getPackageManager()
    {
        if (self::$PackageManager === null) {
            self::$PackageManager = new \QUI\Package\Manager();
        }

        return self::$PackageManager;
    }

    /**
     * Returns the wanted package
     *
     * @param string $package - name of the package eq: quiqqer/blog or quiqqer/quiqqer
     *
     * @return \QUI\Package\Package
     *
     * @throws QUI\Exception
     */
    public static function getPackage($package)
    {
        return self::getPackageManager()->getInstalledPackage($package);
    }

    /**
     * Returns the project manager
     *
     * @return \QUI\Projects\Manager
     */
    public static function getProjectManager()
    {
        if (self::$ProjectManager === null) {
            self::$ProjectManager = new \QUI\Projects\Manager();
        }

        return self::$ProjectManager;
    }

    /**
     * @deprecated use \QUI::getPluginManager()
     */
    public static function getPlugins()
    {
        return self::getPluginManager();
    }

    /**
     * Returns the plugins manager
     *
     * @return \QUI\Plugins\Manager
     */
    public static function getPluginManager()
    {
        if (self::$Plugins === null) {
            self::$Plugins = new \QUI\Plugins\Manager();
        }

        return self::$Plugins;
    }

    /**
     * returns the rewrite object
     *
     * @return \QUI\Rewrite
     */
    public static function getRewrite()
    {
        if (self::$Rewrite === null) {
            self::$Rewrite = new \QUI\Rewrite();
        }

        return self::$Rewrite;
    }

    /**
     * Return the rights object
     *
     * @return \QUI\Permissions\Manager
     *
     * @deprecated use ::getPermissionManager
     */
    public static function getRights()
    {
        return self::getPermissionManager();
    }

    /**
     * Return the rights object
     *
     * @return \QUI\Permissions\Manager
     */
    public static function getPermissionManager()
    {
        if (self::$Rights === null) {
            self::$Rights = new \QUI\Permissions\Manager();
        }

        return self::$Rights;
    }

    /**
     * Return the global request object
     *
     * @return Request
     */
    public static function getRequest()
    {
        if (self::$Request === null) {
            self::$Request = Request::createFromGlobals();
        }

        return self::$Request;
    }

    /**
     * @return Response
     */
    public static function getGlobalResponse()
    {
        if (self::$Response === null) {
            self::$Response = new Response();

            $Headers = new \QUI\System\Headers(self::$Response);
            $Headers->compile();
        }

        return self::$Response;
    }

    /**
     * Return the global QUI Session
     *
     * @return \QUI\Session|QUI\System\Console\Session
     */
    public static function getSession()
    {
        if (\php_sapi_name() === 'cli') {
            if (self::$Session === null) {
                self::$Session = new QUI\System\Console\Session();
            }

            return self::$Session;
        }

        if (self::$Session === null) {
            self::$Session = new \QUI\Session();
            self::getRequest()->setSession(self::$Session->getSymfonySession());
        }

        return self::$Session;
    }

    /**
     * Return the temp manager
     *
     * @return QUI\Temp
     */
    public static function getTemp()
    {
        if (self::$Temp === null) {
            self::$Temp = new \QUI\Temp(VAR_DIR.'tmp');
        }

        return self::$Temp;
    }

    /**
     * Return the Template Manager
     *
     * @return \QUI\Template
     */
    public static function getTemplateManager()
    {
        if (self::$Template === null) {
            self::$Template = new \QUI\Template();
        }

        return self::$Template;
    }

    /**
     * Return the user manager
     *
     * @return \QUI\Users\Manager
     */
    public static function getUsers()
    {
        if (self::$Users === null) {
            self::$Users = new \QUI\Users\Manager();
        }

        return self::$Users;
    }

    /**
     * Get current logged in user
     *
     * @return \QUI\Interfaces\Users\User
     */
    public static function getUserBySession()
    {
        return self::getUsers()->getUserBySession();
    }

    /**
     * Runs QUIQQER in the backend?
     *
     * @return bool
     */
    public static function isBackend()
    {
        return \defined('QUIQQER_BACKEND') && QUIQQER_BACKEND;
    }

    /**
     * Runs QUIQQER in the frontend?
     *
     * @return bool
     */
    public static function isFrontend()
    {
        if (\defined('QUIQQER_BACKEND') && QUIQQER_BACKEND) {
            return false;
        }

        if (\defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE) {
            return false;
        }

        return true;
    }

    /**
     * Runs QUIQQER in the system (console)?
     */
    public static function isSystem()
    {
        return \defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE;
    }
}
