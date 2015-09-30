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
    static $Conf = null;

    /**
     * QUI getDB object, use \QUI::getDB()
     *
     * @var \QUI\Utils\MyDB
     */
    static $DataBase = null;

    /**
     * QUI getDataBase object, use \QUI::getDataBase();
     *
     * @var \QUI\Database\DB
     */
    static $DataBase2 = null;

    /**
     * QUI Error Handler, use \QUI::getErrorHandler();
     *
     * @var \QUI\Exceptions\Handler
     */
    static $ErrorHandler = null;

    /**
     * QUI vhosts, use \QUI::vhosts();
     *
     * @var array
     */
    static $vhosts = null;

    /**
     * Timestamp of the last update
     *
     * @var Integer
     */
    static $last_up_date = null;

    /**
     * QUI Ajax
     *
     * @var \QUI\Ajax
     */
    static $Ajax = null;

    /**
     * QUI GroupManager, use \QUI::getGroups()
     *
     * @var \QUI\Groups\Manager
     */
    static $Groups = null;

    /**
     * QUI Message Handler, use \QUI::getMessageHandler()
     *
     * @var \QUI\Messages\Handler
     */
    static $MessageHandler = null;

    /**
     * QUI Locale Object, use \QUI::getLocale()
     *
     * @var \QUI\Locale
     */
    static $Locale = null;

    /**
     * QUI Mail Manager
     *
     * @var \QUI\Mail\Manager
     */
    static $MailManager = null;

    /**
     * QUI Pluginmanager, use \QUI::getPlugins();
     *
     * @var \QUI\Plugins\Manager
     */
    static $Plugins = null;

    /**
     * QUI Packagemanager, use \QUI::getPackageManager();
     *
     * @var \QUI\Package\Manager
     */
    static $PackageManager = null;

    /**
     * QUI Projectmanager, use \QUI::getProjectManager();
     *
     * @var \QUI\Projects\Manager
     */
    static $ProjectManager = null;

    /**
     * QUI Projectmanager, use \QUI::getProjectManager();
     *
     * @var Request
     */
    static $Request = null;

    /**
     * Global Response Object
     *
     * @var Response
     */
    static $Response = null;

    /**
     * QUI Rewrite Object, use \QUI::getRewrite();
     *
     * @var \QUI\Rewrite
     */
    static $Rewrite = null;

    /**
     * QUI Rights Object, use \QUI::getRights();
     *
     * @var \QUI\Rights\Manager
     */
    static $Rights = null;

    /**
     * QUI Session Object, use \QUI::getSession();
     *
     * @var \QUI\Session
     */
    static $Session = null;

    /**
     * QUI\Temp Object, use \QUI::getTemp();
     *
     * @var QUI\Temp
     */
    static $Temp = null;

    /**
     * QUI User Manager, use \QUI::getUsers();
     *
     * @var \QUI\Users\Manager
     */
    static $Users = null;

    /**
     * internal config objects, array list of configs
     *
     * @var array
     */
    static $Configs = array();

    /**
     * QUI global Events
     *
     * @var \QUI\Events\Manager
     */
    static $Events;

    /**
     * Country Manager
     *
     * @var \QUI\Countries\Manager
     */
    static $Countries = null;

    /**
     * Template Manager
     *
     * @var \QUI\Template
     */
    static $Template = null;

    /**
     * Set all important pathes and load QUIQQER
     */
    static function load()
    {
        // load the main configuration
        $config = parse_ini_file(ETC_DIR . 'conf.ini.php', true);

        /**
         * load the constants
         */

        if (!defined('CMS_DIR')) {
            /**
             * CMS_DIR - Path to the quiqqer folder, where the whole system are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('CMS_DIR', $config['globals']['cms_dir']);
        }

        if (!defined('DEBUG_MODE')) {
            /**
             * DEBUG_MODE - setting if debug mode is enabled or not
             *
             * @var Bool
             * @package com.pcsg.qui
             */
            define("DEBUG_MODE", $config['globals']['debug_mode']);
        }

        if (!defined('DEVELOPMENT')) {
            /**
             * DEVELOPMENT - setting if the system is in development mode or not
             *
             * @var Bool
             * @package com.pcsg.qui
             */
            define("DEVELOPMENT", $config['globals']['development']);
        }

        $var_dir = $config['globals']['var_dir'];

        if (file_exists($var_dir . 'last_update')) {
            self::$last_up_date = file_get_contents($var_dir . 'last_update');

        } else {
            self::$last_up_date = time();
        }

        $lib_dir = dirname(__FILE__) . '/';
        $var_dir = $config['globals']['var_dir'];

        // Define quiqqer path constants

        if (!defined('LIB_DIR')) {
            /**
             * LIB_DIR - Path to the lib folder, where all the libraries are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('LIB_DIR', $lib_dir);
        }

        if (!defined('VAR_DIR')) {
            /**
             * VAR_DIR - Path to the var folder,
             * where all the files are located on which the web server must have access
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('VAR_DIR', $var_dir);
        }

        if (!defined('BIN_DIR')) {
            /**
             * BIN_DIR - Path to the bin folder, where all temp files are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('BIN_DIR', dirname(LIB_DIR) . '/bin/');
        }

        if (!defined('USR_DIR')) {
            /**
             * USR_DIR - Path to the usr folder, where all projects are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('USR_DIR', $config['globals']['usr_dir']);
        }

        if (!defined('SYS_DIR')) {
            /**
             * SYS_DIR - Path to the etc folder, where all the configurations are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('SYS_DIR', dirname(LIB_DIR) . '/admin/');
        }

        if (!defined('OPT_DIR')) {
            /**
             * OPT_DIR - Path to the plugin folder, where all plugins are located
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('OPT_DIR', $config['globals']['opt_dir']);
        }

        if (!defined('URL_DIR')) {
            /**
             * URL_DIR - path by which the system is accessible via the browser
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('URL_DIR', $config['globals']['url_dir']);
        }


        $Config     = new \QUI\Config(ETC_DIR . 'conf.ini.php');
        self::$Conf = $Config;

        if ($Config->getValue('globals', 'timezone')) {
            date_default_timezone_set($Config->getValue('globals', 'timezone'));
        }


        if (!defined('ERROR_BACKTRACE')) {
            /**
             * ERROR_BACKTRACE - configuration,
             * if a backtrace should write in the logs during a error
             *
             * @var String
             * @package com.pcsg.qui
             */
            define('ERROR_BACKTRACE', $Config->get('error', 'backtrace'));
        }

        if (!defined('QUI_DB_PRFX')) {
            /**
             * QUI_DB_PRFX - The DB Table Prefix
             *
             * @var String
             * @package com.pcsg.qui
             */

            $prfx = '';

            if ($Config->get('db', 'prfx')) {
                $prfx = $Config->get('db', 'prfx');
            }

            define('QUI_DB_PRFX', $prfx);
        }


        // create the temp folder
        // @todo better do at the setup
        $folders = array(
            CMS_DIR . 'media/users/',
            // VAR
            VAR_DIR . 'log/',
            VAR_DIR . 'sessions/',
            VAR_DIR . 'uid_sess/',
            VAR_DIR . 'backup/',
            VAR_DIR . 'lock/',
            // Cache - noch nötig?
            VAR_DIR . 'cache/url/',
            VAR_DIR . 'cache/siteobjects/',
            VAR_DIR . 'cache/projects',
            VAR_DIR . 'locale/',
            VAR_DIR . 'tmp/'
        );

        foreach ($folders as $folder) {
            \QUI\Utils\System\File::mkdir($folder);
        }

        // Load Packages
        self::getPackageManager();

        // register ajax
        self::$Ajax = new \QUI\Ajax(array(
            'db_errors' => self::conf('error', 'mysql_ajax_errors_backend')
        ));

        // mem peak - info mail at 80% usage
        self::getErrorHandler()->registerShutdown(function () {
            \QUI\Utils\System\Debug::marker('END');

            // ram peak, if the ram usage is to high, than write and send a message
            $peak = memory_get_peak_usage();
            $mem_limit
                  = \QUI\Utils\System\File::getBytes(ini_get('memory_limit'))
                    * 0.8;

            if ($peak > $mem_limit && $mem_limit > 0) {
                $limit
                    = \QUI\Utils\System\File::formatSize(memory_get_peak_usage());

                if (!isset($_SERVER["HTTP_HOST"])) {
                    $_SERVER["HTTP_HOST"] = '';
                }

                if (!isset($_SERVER["REQUEST_URI"])) {
                    $_SERVER["REQUEST_URI"] = $_SERVER["SCRIPT_FILENAME"];
                }

                if (!isset($_SERVER["HTTP_REFERER"])) {
                    $_SERVER["HTTP_REFERER"] = '';
                }

                $message = "Peak usage: " . $limit . "\n" .
                           "memory_limit: " . ini_get('memory_limit') . "\n" .
                           "URI: " . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\n" .
                           "HTTP_REFERER: " . $_SERVER["HTTP_REFERER"];

                if (\QUI::conf('mail', 'admin_mail')) {
                    \QUI::getMailManager()->send(
                        \QUI::conf('mail', 'admin_mail'),
                        'Memory limit reached at http://' . $_SERVER["HTTP_HOST"],
                        $message
                    );
                }

                \QUI\System\Log::write($message, 'error');
            }
        });


        // there are system changes?
        // then make a setup
        if ($Config->get('globals', 'system_changed')) {
            \QUI\Setup::all();

            $Config->set('globals', 'system_changed', 0);
            $Config->save();
        }
    }

    /**
     * Starts the Setup
     */
    static function setup()
    {
        \QUI\Setup::all();
    }

    /**
     * Get a QUIQQER main configuration entry
     *
     * @param String $section
     * @param String|null $key (optional)
     *
     * @return mixed
     */
    static function conf($section, $key = null)
    {
        if (is_null(self::$Conf)) {
            self::$Conf = self::getConfig('etc/conf.ini.php');
        }

        return self::$Conf->get($section, $key);
    }

    /**
     * Returns all available languages
     *
     * @return Array
     */
    static function availableLanguages()
    {
        $langs = QUI\Translator::langs();

        if (empty($langs)) {
            $langs = array('en');
        }

        return $langs;
    }

    /**
     * Return the QUIQQER version
     *
     * @return String
     */
    static function version()
    {
        return self::getPackageManager()->getVersion();
    }

    /**
     * Get registered vhosts
     *
     * @return Array
     */
    static function vhosts()
    {
        if (!is_null(self::$vhosts)) {
            return self::$vhosts;
        }

        try {
            $vhosts       = self::getConfig('etc/vhosts.ini.php');
            self::$vhosts = $vhosts->toArray();

        } catch (\QUI\Exception $Exception) {
            self::$vhosts = array();
        }

        return self::$vhosts;
    }

    /**
     * Return the tablename with the QUI Prefix
     *
     * @param String $table
     *
     * @return String
     */
    static function getDBTableName($table)
    {
        return QUI_DB_PRFX . $table;
    }

    /**
     * Return the tablename with the QUI Prefix and table params
     *
     * @param String $table
     * @param \QUI\Projects\Project
     * @param Bool $lang - language in the table name? default = true
     *
     * @return String
     */
    static function getDBProjectTableName(
        $table,
        \QUI\Projects\Project $Project,
        $lang = true
    ) {
        if ($lang === false) {
            return QUI_DB_PRFX . $Project->getName() . '_' . $table;
        }

        return QUI_DB_PRFX . $Project->getName() . '_' . $Project->getLang() . '_'
               . $table;
    }

    /**
     * Returns a config object for a INI file
     * Starting from CMS_DIR
     *
     * @param String $file
     *
     * @throws \QUI\Exception
     *
     * @return \QUI\Config
     */
    static function getConfig($file)
    {
        if (isset(self::$Configs[$file])) {
            return self::$Configs[$file];
        }

        $_file = CMS_DIR . $file;

        if (substr($file, -4) !== '.php') {
            $_file .= '.php';
        }

        if (!isset(self::$Configs[$file])) {
            if (!file_exists($_file) || is_dir($_file)) {
                throw new \QUI\Exception(
                    'Error: Ini Datei: ' . $_file . ' existiert nicht.',
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
    static function getCountries()
    {
        if (is_null(self::$Countries)) {
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
    static function getDB()
    {
        if (is_null(self::$DataBase)) {
            self::$DataBase = new \QUI\Utils\MyDB();
        }

        return self::$DataBase;
    }

    /**
     * Returns the Database object
     *
     * @return \QUI\Database\DB
     */
    static function getDataBase()
    {
        if (is_null(self::$DataBase2)) {
            self::$DataBase2 = new \QUI\Database\DB(array(
                'driver'   => self::conf('db', 'driver'),
                'host'     => self::conf('db', 'host'),
                'user'     => self::conf('db', 'user'),
                'password' => self::conf('db', 'password'),
                'dbname'   => self::conf('db', 'database')
            ));
        }

        return self::$DataBase2;
    }

    /**
     * Returns the globals Events object
     *
     * @return \QUI\Events\Manager
     */
    static function getEvents()
    {
        if (is_null(self::$Events)) {
            self::$Events = new \QUI\Events\Manager();
        }

        return self::$Events;
    }

    /**
     * Returns the PDO Database object
     *
     * @return \PDO
     */
    static function getPDO()
    {
        return self::getDataBase()->getPDO();
    }

    /**
     * Returns a Project
     * It use the \QUI\Projects\Manager
     *
     * You can also use \QUI\Projects\Manager::getProject()
     *
     * @param String|Array $project - Project name | array('name' => , 'lang' => , 'template' => )
     * @param String|Bool $lang - Project lang (optional)
     * @param String|Bool $template - Project template (optional)
     *
     * @return \QUI\Projects\Project
     * @uses \QUI\Projects\Manager
     */
    static function getProject($project, $lang = false, $template = false)
    {
        if (is_array($project)) {
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
    static function getErrorHandler()
    {
        if (is_null(self::$ErrorHandler)) {
            require_once dirname(__FILE__) . '/QUI/Exceptions/Handler.php';

            self::$ErrorHandler = new \QUI\Exceptions\Handler();

            self::$ErrorHandler->setAttribute(
                'logdir',
                self::conf('globals', 'var_dir') . 'log/'
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
    static function getGroups()
    {
        if (is_null(self::$Groups)) {
            self::$Groups = new \QUI\Groups\Manager();
        }

        return self::$Groups;
    }

    /**
     * Returns the QUIQQER message handler object
     *
     * @return \QUI\Messages\Handler
     */
    static function getMessagesHandler()
    {
        if (is_null(self::$MessageHandler)) {
            self::$MessageHandler = new \QUI\Messages\Handler();
        }

        return self::$MessageHandler;
    }

    /**
     * Returns the main locale object
     *
     * @return \QUI\Locale
     */
    static function getLocale()
    {
        if (is_null(self::$Locale)) {
            self::$Locale = new \QUI\Locale();
        }

        return self::$Locale;
    }

    /**
     * Return the mail manager
     *
     * @return \QUI\Mail\Manager
     */
    static function getMailManager()
    {
        if (is_null(self::$MailManager)) {
            self::$MailManager = new \QUI\Mail\Manager();
        }

        return self::$MailManager;
    }

    /**
     * Returns the package manager
     *
     * @return \QUI\Package\Manager
     */
    static function getPackageManager()
    {
        if (is_null(self::$PackageManager)) {
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
     */
    static function getPackage($package)
    {
        return self::getPackageManager()->getInstalledPackage($package);
    }

    /**
     * Returns the project manager
     *
     * @return \QUI\Projects\Manager
     */
    static function getProjectManager()
    {
        if (is_null(self::$ProjectManager)) {
            self::$ProjectManager = new \QUI\Projects\Manager();
        }

        return self::$ProjectManager;
    }

    /**
     * @deprecated use \QUI::getPluginManager()
     */
    static function getPlugins()
    {
        return self::getPluginManager();
    }

    /**
     * Returns the plugins manager
     *
     * @return \QUI\Plugins\Manager
     */
    static function getPluginManager()
    {
        if (is_null(self::$Plugins)) {
            self::$Plugins = new \QUI\Plugins\Manager();
        }

        return self::$Plugins;
    }

    /**
     * returns the rewrite object
     *
     * @return \QUI\Rewrite
     */
    static function getRewrite()
    {
        if (is_null(self::$Rewrite)) {
            self::$Rewrite = new \QUI\Rewrite();
        }

        return self::$Rewrite;
    }

    /**
     * Return the rights object
     *
     * @return \QUI\Rights\Manager
     *
     * @deprecated use ::getPermissionManager
     */
    static function getRights()
    {
        return self::getPermissionManager();
    }

    /**
     * Return the rights object
     *
     * @return \QUI\Rights\Manager
     */
    static function getPermissionManager()
    {
        if (is_null(self::$Rights)) {
            self::$Rights = new \QUI\Rights\Manager();
        }

        return self::$Rights;
    }

    /**
     * Return the global request object
     *
     * @return Request
     */
    static function getRequest()
    {
        if (is_null(self::$Request)) {
            self::$Request = Request::createFromGlobals();
        }

        return self::$Request;
    }

    /**
     * @return Response
     */
    static function getGlobalResponse()
    {
        if (is_null(self::$Response)) {
            self::$Response = new Response();
            self::$Response->setCharset('UTF-8');
            self::$Response->headers->set('Content-Type', 'text/html');
        }

        return self::$Response;
    }

    /**
     * Return the global QUI Session
     *
     * @return \QUI\Session
     */
    static function getSession()
    {
        if (is_null(self::$Session)) {
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
    static function getTemp()
    {
        if (is_null(self::$Temp)) {
            self::$Temp = new \QUI\Temp(VAR_DIR . 'tmp');
        }

        return self::$Temp;
    }

    /**
     * Return the Template Manager
     *
     * @return \QUI\Template
     */
    static function getTemplateManager()
    {
        if (is_null(self::$Template)) {
            self::$Template = new \QUI\Template();
        }

        return self::$Template;
    }

    /**
     * Return the user manager
     *
     * @return \QUI\Users\Manager
     */
    static function getUsers()
    {
        if (is_null(self::$Users)) {
            self::$Users = new \QUI\Users\Manager();
        }

        return self::$Users;
    }

    /**
     * Get current logged in user
     *
     * @return \QUI\Users\User
     * @uses \QUI\Users\Manager
     */
    static function getUserBySession()
    {
        return self::getUsers()->getUserBySession();
    }

    /**
     * Runs QUIQQER in the backend?
     *
     * @return bool
     */
    static function isBackend()
    {
        if (defined('QUIQQER_BACKEND') && QUIQQER_BACKEND) {
            return true;
        }

        return false;
    }

    /**
     * Runs QUIQQER in the frontend?
     *
     * @return bool
     */
    static function isFrontend()
    {
        if (defined('QUIQQER_BACKEND') && QUIQQER_BACKEND) {
            return false;
        }

        if (defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE) {
            return false;
        }

        return true;
    }

    /**
     * Runs QUIQQER in the system (console)?
     */
    static function isSystem()
    {
        if (defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE) {
            return true;
        }

        return false;
    }
}
