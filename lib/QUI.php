<?php

/**
 * This file contains QUI
 */

/**
 * The Main Object of the QUIQQER Management System
 *
 * @example
 * QUI::conf();
 * QUI::getDataBase();
 * QUI::getPDO();
 * QUI::getLocale();
 * and so on
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI
{
    /**
     * QUI Config, use QUI::getConfig()
     * @var QConfig
     */
    static $Conf = null;

    /**
     * QUI getDB object, use QUI::getDB()
     * @var Utils_MyDB
     */
    static $DataBase = null;

    /**
     * QUI getDataBase object, use QUI::getDataBase();
     * @var Utils_Db
     */
    static $DataBase2 = null;

    /**
     * QUI Error Handler, use QUI::getErrorHandler();
     * @var QExceptionHandler
     */
    static $ErrorHandler = null;

    /**
     * QUI vhosts, use QUI::vhosts();
     * @var array
     */
    static $vhosts = null;

    /**
     * Timestamp of the last update
     * @var Integer
     */
    static $last_up_date = null;

    /**
     * QUI Ajax
     * @var Utils_Request_Ajax
     */
    static $Ajax = null;

    /**
     * QUI GroupManager, use QUI::getGroups()
     * @var Groups_Groups
     */
    static $Groups = null;

    /**
     * QUI Message Handler, use QUI::getMessageHandler()
     * @var QUI_Messages_Handler
     */
    static $MessageHandler = null;

    /**
     * QUI Licence, use QUI::getLicence()
     * @var QUI_Licence
     */
    static $Licence = null;

    /**
     * QUI Locale Object, use QUI::getLocale()
     * @var QUI_Locale
     */
    static $Locale = null;

    /**
     * QUI Pluginmanager, use QUI::getPlugins();
     * @var QUI_Plugins_Manager
     */
    static $Plugins  = null;

    /**
     * QUI Packagemanager, use QUI::getPackageManager();
     * @var QUI_Package_Manager
     */
    static $PackageManager = null;

    /**
     * QUI Rewrite Object, use QUI::getRewrite();
     * @var QUI_Rewrite
     */
    static $Rewrite = null;

    /**
     * QUI Rights Object, use QUI::getRights();
     * @var QUI_Rights_Manager
     */
    static $Rights = null;

    /**
     * QUI Session Object, use QUI::getSession();
     * @var QUI_Session
     */
    static $Session = null;

    /**
     * QUI\Temp Object, use QUI::getTemp();
     * @var QUI\Temp
     */
    static $Temp = null;

    /**
     * QUI User Manager, use QUI::getUsers();
     * @var Users_Users
     */
    static $Users = null;

    /**
     * internal config objects, array list of configs
     * @var array
     */
    static $Configs = array();

    /**
     * QUI global Events
     * @var QUI_Events
     */
    static $Events;

    /**
     * Country Manager
     * @var Utils_Countries_Manager
     */
    static $Countries = null;

    /**
     * Set all important pathes and load QUIQQER
     */
    static function load()
    {
        require 'QException.php';
        require 'QConfig.php';

        // load the main configuration
        $path    = pathinfo( __FILE__ );
        $cms_dir = str_replace( DIRECTORY_SEPARATOR .'lib', '', $path['dirname'] );

        $Config     = new QConfig( $cms_dir .'/etc/conf.ini' );
        self::$Conf = $Config;

        if ( !defined( 'CMS_DIR' ) )
        {
            /**
             * CMS_DIR - Path to the quiqqer folder, where the whole system are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'CMS_DIR', $Config->get( 'globals', 'cms_dir' ) );
        }

        /**
         * DEBUG_MODE - setting if debug mode is enabled or not
         * @var Bool
         * @package com.pcsg.qui
         */
        define( "DEBUG_MODE", $Config->get( 'globals', 'debug_mode' ) );

        /**
         * DEVELOPMENT - setting if the system is in development mode or not
         * @var Bool
         * @package com.pcsg.qui
         */
        define( "DEVELOPMENT", $Config->get( 'globals', 'development' ) );

        $var_dir = $Config->get( 'globals', 'var_dir' );

        if ( file_exists( $var_dir .'last_update' ) )
        {
            self::$last_up_date = file_get_contents( $var_dir .'last_update' );

        } else
        {
            self::$last_up_date = time();
        }

        $lib_dir = $Config->get( 'globals', 'lib_dir' );
        $var_dir = $Config->get( 'globals', 'var_dir' );

        // Define quiqqer path constants

        if ( !defined( 'LIB_DIR' ) )
        {
            /**
             * LIB_DIR - Path to the lib folder, where all the libraries are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'LIB_DIR', $lib_dir );
        }

        if ( !defined( 'VAR_DIR' ) )
        {
            /**
             * VAR_DIR - Path to the var folder,
             * where all the files are located on which the web server must have access
             *
             * @var String
             * @package com.pcsg.qui
             */
            define( 'VAR_DIR', $var_dir );
        }

        if ( !defined( 'BIN_DIR' ) )
        {
            /**
             * BIN_DIR - Path to the bin folder, where all temp files are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'BIN_DIR', $Config->get( 'globals','bin_dir' ) );
        }

        if ( !defined( 'USR_DIR' ) )
        {
            /**
             * USR_DIR - Path to the usr folder, where all projects are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'USR_DIR', $Config->get( 'globals','usr_dir' ) );
        }

        if ( !defined('SYS_DIR') )
        {
            /**
             * SYS_DIR - Path to the etc folder, where all the configurations are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'SYS_DIR', $Config->get( 'globals','sys_dir' ) );
        }

        if ( !defined( 'OPT_DIR' ) )
        {
            /**
             * OPT_DIR - Path to the plugin folder, where all plugins are located
             * @var String
             * @package com.pcsg.qui
             */
            define( 'OPT_DIR', $Config->get( 'globals','opt_dir' ) );
        }

        if ( !defined( 'URL_DIR' ) )
        {
            /**
             * URL_DIR - path by which the system is accessible via the browser
             * @var String
             * @package com.pcsg.qui
             */
            define( 'URL_DIR', $Config->get( 'globals','url_dir' ) );
        }

        if ( !defined( 'ERROR_BACKTRACE' ) )
        {
            /**
             * ERROR_BACKTRACE - configuration,
             * if a backtrace should write in the logs during a error
             *
             * @var String
             * @package com.pcsg.qui
             */
            define( 'ERROR_BACKTRACE', $Config->get( 'error', 'backtrace' ) );
        }

        if ( !defined( 'QUI_DB_PRFX' ) )
        {
            /**
             * QUI_DB_PRFX - The DB Table Prefix
             *
             * @var String
             * @package com.pcsg.qui
             */

            $prfx = '';

            if ( $Config->get( 'db','prfx' ) ) {
                $prfx = $Config->get( 'db','prfx' );
            }

            define( 'QUI_DB_PRFX', $prfx );
        }


        require_once $lib_dir .'autoload.php';

        // create the temp folder
        // @todo better do at the setup
        $folders = array(
            CMS_DIR .'media/users/',

            // VAR
            VAR_DIR .'log/',
            VAR_DIR .'sessions/',
            VAR_DIR .'uid_sess/',
            VAR_DIR .'backup/',
            VAR_DIR .'marcate/',

            // Cache - noch nötig?
            VAR_DIR .'cache/url/',
            VAR_DIR .'cache/siteobjects/',
            VAR_DIR .'cache/projects',

            VAR_DIR .'locale/',
            VAR_DIR .'tmp/'
        );

        foreach ( $folders as $folder ) {
            Utils_System_File::mkdir( $folder );
        }

        // Load Packages
        $QPM = self::getPackageManager();

        // register ajax
        QUI::$Ajax = new Utils_Request_Ajax(array(
            'db_errors' => self::conf( 'error', 'mysql_ajax_errors_backend' )
        ));

        // mem peak - info mail at 80% usage
        QUI::getErrorHandler()->registerShutdown(function()
        {
            // DB Verbindung schließen
            QUI::getDB()->close();
            System_Debug::marker('END');

            // ram peak, if the ram usage is to high, than write and send a message
            $peak      = memory_get_peak_usage();
            $mem_limit = Utils_System_File::getBytes( ini_get( 'memory_limit' ) ) * 0.8;

            if ( $peak > $mem_limit && $mem_limit > 0 )
            {
                $limit = Utils_System_File::formatSize( memory_get_peak_usage() );

                if ( !isset( $_SERVER["HTTP_HOST"] ) ) {
                    $_SERVER["HTTP_HOST"] = '';
                }

                if ( !isset( $_SERVER["REQUEST_URI"] ) ) {
                    $_SERVER["REQUEST_URI"] = $_SERVER["SCRIPT_FILENAME"];
                }

                if ( !isset( $_SERVER["HTTP_REFERER"] ) ) {
                    $_SERVER["HTTP_REFERER"] = '';
                }

                $message = "Peak usage: ". $limit ."\n".
                           "memory_limit: ". ini_get( 'memory_limit' ) ."\n".
                           "URI: ". $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] ."\n".
                           "HTTP_REFERER: ". $_SERVER["HTTP_REFERER"];

                if ( QUI::conf( 'mail','admin_mail' ) )
                {
                    QUI_Mail::init()->send(array(
                         'MailTo'  => QUI::conf( 'mail','admin_mail' ),
                         'Subject' => 'Memory limit reached at http://'. $_SERVER["HTTP_HOST"],
                         'Body'    => $message,
                         'IsHTML'  => false
                    ));
                }

                System_Log::write( $message, 'error' );
            }
        });

        // there are system changes?
        // then make a setup
        if ( $Config->get( 'globals', 'system_changed' ) )
        {
            QUI_Setup::all();

            $Config->set( 'globals', 'system_changed', 0 );
            $Config->save();
        }
    }

    /**
     * Starts the Setup
     */
    static function setup()
    {
        QUI_Setup::all();
    }

    /**
     * Get a QUIQQER main configuration entry
     *
     * @param String $section
     * @param String $key
     */
    static function conf($section, $key=null)
    {
        if ( is_null( self::$Conf ) ) {
             self::$Conf = self::getConfig( 'etc/conf.ini' );
        }

        return self::$Conf->get( $section, $key );
    }

    /**
     * Retrusn all available languages
     *
     * @return Array
     */
    static function availableLanguages()
    {
        $projects = Projects_Manager::getConfig()->toArray();
        $langs    = array();

        foreach ( $projects as $project ) {
            $langs = array_merge( $langs, explode( ',', $project['langs'] ) );
        }

        $langs = array_unique( $langs );

        return $langs;
    }

    /**
     * Get registered vhosts
     *
     * @return Array
     */
    static function vhosts()
    {
        if ( !is_null( self::$vhosts ) ) {
            return self::$vhosts;
        }

        try
        {
            $vhosts = self::getConfig( 'etc/vhosts.ini' );
            self::$vhosts = $vhosts->toArray();

        } catch ( \QException $Exception )
        {
            self::$vhosts = array();
        }

        return self::$vhosts;
    }

    /**
     * Return the tablename with the QUI Prefix
     *
     * @param String $table
     * @return String
     */
    static function getDBTableName($table)
    {
        return QUI_DB_PRFX . $table;
    }

    /**
     * Returns a config object for a INI file
     * Starting from CMS_DIR
     *
     * @param String $file
     * @throws QException
     *
     * @return QConfig
     */
    static function getConfig($file)
    {
        if ( !isset( self::$Configs[ $file ] ) )
        {
            if ( !file_exists( CMS_DIR . $file ) || is_dir( CMS_DIR . $file ) )
            {
                throw new QException(
                    'Error: Ini Datei: '. $file .' existiert nicht.',
                    404
                );
            }

            self::$Configs[ $file ] = new QConfig( CMS_DIR . $file );
        }

        return self::$Configs[ $file ];
    }

    /**
     * Returns the Country Manager
     * @return Utils_Countries_Manager
     */
    static function getCountries()
    {
        if ( is_null( self::$Countries ) ) {
            self::$Countries = new Utils_Countries_Manager();
        }

        return self::$Countries;
    }

    /**
     * Returns the Datebase Object (old version)
     *
     * @return Utils_MyDB
     * @deprecated
     * use getDataBase and PDO or direct getPDO
     */
    static function getDB()
    {
        if ( is_null( self::$DataBase ) ) {
            self::$DataBase = new Utils_MyDB();
        }

        return self::$DataBase;
    }

    /**
     * Returns the Database object
     *
     * @return Utils_Db
     */
    static function getDataBase()
    {
        if ( is_null( self::$DataBase2 ) )
        {
            self::$DataBase2 = new Utils_Db(array(
                'driver'   => self::conf( 'db', 'driver' ),
                'host'     => self::conf( 'db', 'host' ),
                'user'     => self::conf( 'db', 'user' ),
                'password' => self::conf( 'db', 'password' ),
                'dbname'   => self::conf( 'db', 'database' )
            ));
        }

        return self::$DataBase2;
    }

    /**
     * Returns the globals Events object
     *
     * @return QUI_Events_Manager
     */
    static function getEvents()
    {
        if ( is_null( self::$Events ) ) {
            self::$Events = new QUI_Events_Manager();
        }

        return self::$Events;
    }

    /**
     * Returns the PDO Database object
     * @return PDO
     */
    static function getPDO()
    {
        return self::getDataBase()->getPDO();
    }

    /**
     * Returns a Project
     * It use the Projects_Manager
     *
     * You can also use Projects_Manager::getProject()
     *
     * @param String $project 	- Project name
     * @param String $lang		- Project lang (optional)
     * @param String $template  - Project template (optional)
     *
     * @return Projects_Project
     * @uses Projects_Manager
     */
    static function getProject($project, $lang=false, $template=false)
    {
        return Projects_Manager::getProject( $project, $lang, $template );
    }

    /**
     * Returns the ErrorHandler
     * @return QExceptionHandler
     */
    static function getErrorHandler()
    {
        if ( is_null(self::$ErrorHandler) )
        {
            require_once 'QExceptionHandler.php';

            self::$ErrorHandler = new QExceptionHandler();

            self::$ErrorHandler->setAttribute(
                'logdir',
                self::conf( 'globals','var_dir' ) .'log/'
            );

            self::$ErrorHandler->setAttribute(
                'backtrace',
                self::conf( 'error', 'backtrace' )
            );
        }

        return self::$ErrorHandler;
    }

    /**
     * Returns the group manager
     * @return Groups_Groups
     */
    static function getGroups()
    {
        if ( is_null( self::$Groups ) ) {
            self::$Groups = new Groups_Groups();
        }

        return self::$Groups;
    }

    /**
     * Returns the QUIQQER message handler object
     * @return QUI_Messages_Handler
     */
    static function getMessagesHandler()
    {
        if ( is_null( self::$MessageHandler ) ) {
            self::$MessageHandler = new QUI_Messages_Handler();
        }

        return self::$MessageHandler;
    }

    /**
     * Returns the QUIQQER licence object
     * @return QUI_Licence
     */
    static function getLicence()
    {
        if ( is_null( self::$Licence ) ) {
            self::$Licence = new QUI_Licence();
        }

        return self::$Licence;
    }

    /**
     * Returns the main locale object
     * @return QUI_Locale
     */
    static function getLocale()
    {
        if ( is_null( self::$Locale ) ) {
            self::$Locale = new QUI_Locale();
        }

        return self::$Locale;
    }

    /**
     * Returns the package manager
     * @return QUI_Package_Manager
     */
    static function getPackageManager()
    {
        if ( is_null( self::$PackageManager ) ) {
            self::$PackageManager = new QUI_Package_Manager();
        }

        return self::$PackageManager;
    }

    /**
     * Returns the plugins manager
     * @return QUI_Plugins_Manager
     */
    static function getPlugins()
    {
        if ( is_null( self::$Plugins ) ) {
            self::$Plugins = new QUI_Plugins_Manager();
        }

        return self::$Plugins;
    }

    /**
     * returns the rewrite object
     * @return QUI_Rewrite
     */
    static function getRewrite()
    {
        if ( is_null( self::$Rewrite ) ) {
            self::$Rewrite = new QUI_Rewrite();
        }

        return self::$Rewrite;
    }

    /**
     * Return the rights object
     * @return QUI_Rights_Manager
     * @deprecated use ::getPermissionManager
     */
    static function getRights()
    {
        if ( is_null( self::$Rights ) ) {
            self::$Rights = new QUI_Rights_Manager();
        }

        return self::$Rights;
    }

    /**
     * Return the rights object
     * @return QUI_Rights_Manager
     */
    static function getPermissionManager()
    {
        if ( is_null( self::$Rights ) ) {
            self::$Rights = new QUI_Rights_Manager();
        }

        return self::$Rights;
    }

    /**
     * Return the global QUI Session
     * @return QUI_Session
     */
    static function getSession()
    {
        if ( is_null( self::$Session ) ) {
            self::$Session = new QUI_Session();
        }

        return self::$Session;
    }

    /**
     * Return the temp manager
     * @return QUI\Temp
     */
    static function getTemp()
    {
        if ( is_null( self::$Temp ) ) {
            self::$Temp = new \QUI\Temp( VAR_DIR .'temp' );
        }

        return self::$Temp;
    }

    /**
     * Return the user manager
     * @return Users_Users
     */
    static function getUsers()
    {
        if ( is_null( self::$Users ) ) {
            self::$Users = new Users_Users();
        }

        return self::$Users;
    }

    /**
     * Get current logged in user
     * @return Users_User
     * @uses Users_Users
     */
    static function getUserBySession()
    {
        return self::getUsers()->getUserBySession();
    }
}

?>