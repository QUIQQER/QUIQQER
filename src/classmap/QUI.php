<?php

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace

/**
 * This file contains QUI
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use QUI\Ajax;
use QUI\Config;
use QUI\Database\DB;
use QUI\Exception;
use QUI\Interfaces\Users\User;
use QUI\Package\Package;
use QUI\Projects\Manager as ProjectManager;
use QUI\Permissions\Manager as PermissionManager;
use QUI\Projects\Project;
use QUI\Users\Manager as UserManager;
use QUI\Groups\Manager as GroupManager;
use QUI\Messages\Handler as Messages;
use QUI\Mail\Manager as MailManager;
use QUI\Package\Manager as PackageManager;
use QUI\Events\Manager as EventsManager;
use QUI\Countries\Manager as CountriesManager;
use QUI\Rewrite;
use QUI\Session;
use QUI\System\Headers;
use QUI\System\Log;
use QUI\Temp;
use QUI\Template;
use QUI\Utils\MyDB;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
     */
    public static ?Config $Conf = null;

    /**
     * QUI getDB object, use \QUI::getDB()
     */
    public static ?MyDB $DataBase = null;

    /**
     * QUI getDataBase object, use \QUI::getDataBase();
     */
    public static ?DB $DataBase2 = null;

    protected static ?Connection $QueryBuilder = null;

    /**
     * QUI Error Handler, use \QUI::getErrorHandler();
     */
    public static ?QUI\Exceptions\Handler $ErrorHandler = null;

    /**
     * QUI vhosts, use \QUI::vhosts();
     */
    public static ?array $vhosts = null;

    /**
     * QUI Ajax
     */
    public static ?Ajax $Ajax = null;

    /**
     * QUI GroupManager, use \QUI::getGroups()
     */
    public static ?GroupManager $Groups = null;

    /**
     * QUI Message Handler, use \QUI::getMessageHandler()
     */
    public static ?Messages $MessageHandler = null;

    /**
     * QUI Locale Object, use \QUI::getLocale()
     */
    public static ?QUI\Locale $Locale = null;

    /**
     * QUI Mail Manager
     */
    public static ?MailManager $MailManager = null;

    /**
     * QUI project manager, use \QUI::getPackageManager();
     */
    public static ?PackageManager $PackageManager = null;

    /**
     * QUI project manager, use \QUI::getProjectManager();
     */
    public static ?ProjectManager $ProjectManager = null;

    /**
     * QUI project manager, use \QUI::getProjectManager();
     */
    public static ?Request $Request = null;

    /**
     * Global Response Object
     */
    public static ?Response $Response = null;

    /**
     * QUI Rewrite Object, use \QUI::getRewrite();
     */
    public static ?Rewrite $Rewrite = null;

    /**
     * QUI Rights Object, use \QUI::getRights();
     */
    public static ?PermissionManager $Rights = null;

    /**
     * QUI Session Object, use \QUI::getSession();
     *
     * @var Session|QUI\System\Console\Session|null
     */
    public static QUI\System\Console\Session|null|Session $Session = null;

    /**
     * QUI\Temp Object, use \QUI::getTemp();
     */
    public static ?Temp $Temp = null;

    /**
     * QUI User Manager, use \QUI::getUsers();
     */
    public static ?UserManager $Users = null;

    /**
     * internal config objects, array list of configs
     */
    public static array $Configs = [];

    /**
     * QUI global Events
     */
    public static ?EventsManager $Events = null;

    /**
     * Country Manager
     */
    public static ?CountriesManager $Countries = null;

    /**
     * Template Manager
     */
    public static ?Template $Template = null;

    /**
     * QUI default Locale Object
     */
    protected static ?QUI\Locale $SystemLocale = null;

    private static bool $runtimeCacheEnabled = true;

    /**
     * Set all important paths and load QUIQQER
     *
     * @throws QUI\Exception
     */
    public static function load(): void
    {
        // load the main configuration
        $config = parse_ini_file(ETC_DIR . 'conf.ini.php', true);

        /**
         * load the constants
         */

        if (!defined('CMS_DIR')) {
            /**
             * CMS_DIR - Path to the quiqqer folder, where the whole system are located
             */
            define('CMS_DIR', $config['globals']['cms_dir']);
        }

        if (!defined('DEBUG_MODE')) {
            /**
             * DEBUG_MODE - setting if debug mode is enabled or not
             */
            define("DEBUG_MODE", (bool)$config['globals']['debug_mode']);
        }

        if (!defined('DEVELOPMENT')) {
            /**
             * DEVELOPMENT - setting if the system is in development mode or not
             */
            define("DEVELOPMENT", (bool)$config['globals']['development']);
        }

        $var_dir = $config['globals']['var_dir'];
        $lib_dir = dirname(__FILE__, 2) . '/';

        // Define quiqqer path constants

        if (!defined('LIB_DIR')) {
            /**
             * LIB_DIR - Path to the lib folder, where all the libraries are located
             */
            define('LIB_DIR', $lib_dir);
        }

        if (!defined('VAR_DIR')) {
            /**
             * VAR_DIR - Path to the var folder,
             * where all the files are located on which the web server must have access
             */
            define('VAR_DIR', $var_dir);
        }

        if (!defined('BIN_DIR')) {
            /**
             * BIN_DIR - Path to the bin folder, where all temp files are located
             */
            define('BIN_DIR', dirname(LIB_DIR) . '/bin/');
        }

        if (!defined('USR_DIR')) {
            /**
             * USR_DIR - Path to the usr folder, where all projects are located
             */
            define('USR_DIR', $config['globals']['usr_dir']);
        }

        if (!defined('SYS_DIR')) {
            /**
             * SYS_DIR - Path to the admin folder, where all the configurations are located
             */
            define('SYS_DIR', dirname(LIB_DIR) . '/admin/');
        }

        if (!defined('OPT_DIR')) {
            /**
             * OPT_DIR - Path to the plugin folder, where all plugins are located
             */
            define('OPT_DIR', $config['globals']['opt_dir']);
        }

        if (!defined('URL_DIR')) {
            /**
             * URL_DIR - path by which the system is accessible via the browser
             */
            define('URL_DIR', $config['globals']['url_dir']);
        }


        $Config = new QUI\Config(ETC_DIR . 'conf.ini.php');
        self::$Conf = $Config;

        if ($Config->getValue('globals', 'timezone')) {
            date_default_timezone_set($Config->getValue('globals', 'timezone'));
        }


        if (!defined('ERROR_BACKTRACE')) {
            /**
             * ERROR_BACKTRACE - configuration,
             * if a backtrace should write in the logs during an error
             */
            define('ERROR_BACKTRACE', $Config->get('error', 'backtrace'));
        }

        if (!defined('QUI_DB_PRFX')) {
            /**
             * QUI_DB_PRFX - The DB Table Prefix
             */
            $prfx = '';

            if ($Config->get('db', 'prfx')) {
                $prfx = $Config->get('db', 'prfx');
            }

            define('QUI_DB_PRFX', $prfx);
        }

        // create the temp folder
        // @todo better do at the setup
        $folders = [
            // VAR
            VAR_DIR . 'log/',
            VAR_DIR . 'sessions/',
            VAR_DIR . 'uid_sess/',
            VAR_DIR . 'backup/',
            VAR_DIR . 'lock/',

            // Cache - noch nötig?
            VAR_DIR . 'locale/',
            VAR_DIR . 'tmp/' // @todo temp
        ];

        foreach ($folders as $folder) {
            QUI\Utils\System\File::mkdir($folder);
        }


        if (!defined('URL_LIB_DIR')) {
            define('URL_LIB_DIR', QUI::conf('globals', 'url_lib_dir'));
        }

        if (!defined('URL_BIN_DIR')) {
            define('URL_BIN_DIR', QUI::conf('globals', 'url_bin_dir'));
        }

        if (!defined('URL_SYS_DIR')) {
            define('URL_SYS_DIR', QUI::conf('globals', 'url_sys_dir'));
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

        // bugfix: workaround: Uncaught Error: Call to undefined function DusanKasan\Knapsack\append()
        if (
            !function_exists('\DusanKasan\Knapsack\append')
            && file_exists(OPT_DIR . 'martinvenus/knapsack/src/collection_functions.php')
        ) {
            require_once OPT_DIR . 'martinvenus/knapsack/src/collection_functions.php';
        }


        // Load Packages
        self::getPackageManager();


        // mem peak - info mail at 80% usage
        self::getErrorHandler()->registerShutdown(static function (): void {
            QUI\Utils\System\Debug::marker('END');

            // ram peak, if the ram usage is too high, than write and send a message
            $peak = memory_get_peak_usage();
            $mem_limit = QUI\Utils\System\File::getBytes(ini_get('memory_limit')) * 0.8;

            if ($peak > $mem_limit && $mem_limit > 0) {
                $limit = QUI\Utils\System\File::formatSize(
                    memory_get_peak_usage()
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

                $message = "Peak usage: " . $limit . "\n" .
                    "memory_limit: " . ini_get('memory_limit') . "\n" .
                    "URI: " . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "\n" .
                    "HTTP_REFERER: " . $_SERVER["HTTP_REFERER"];

                if (self::conf('mail', 'admin_mail')) {
                    self::getMailManager()->send(
                        self::conf('mail', 'admin_mail'),
                        'Memory limit reached at https://' . $_SERVER["HTTP_HOST"],
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

        QUI::getEvents()->fireEvent('quiqqerInit');
    }

    /**
     * Get a QUIQQER main configuration entry
     *
     * @param string|null $key (optional)
     *
     */
    public static function conf(string $section, string $key = null): mixed
    {
        if (self::$Conf === null) {
            try {
                self::$Conf = self::getConfig('etc/conf.ini.php');
            } catch (QUI\Exception $Exception) {
                Log::writeException($Exception);
                return false;
            }
        }

        return self::$Conf->get($section, $key);
    }

    /**
     * Returns a config object for a INI file
     * Starting from CMS_DIR
     *
     *
     * @throws Exception
     */
    public static function getConfig(string $file): Config
    {
        if (isset(self::$Configs[$file])) {
            return self::$Configs[$file];
        }

        if (defined('CMS_DIR')) {
            $cmsDir = CMS_DIR;
        } else {
            $cmsDir = dirname(__FILE__, 6);
        }

        $_file = $cmsDir . $file;

        if (!str_ends_with($file, '.php')) {
            $_file .= '.php';
        }

        if (!isset(self::$Configs[$file])) {
            if (!file_exists($_file) || is_dir($_file)) {
                throw new Exception(
                    'Error: Ini file does not exists: ' . $_file,
                    404
                );
            }

            self::$Configs[$file] = new Config($_file);
        }

        return self::$Configs[$file];
    }

    /**
     * Returns the package manager
     */
    public static function getPackageManager(): PackageManager
    {
        if (self::$PackageManager === null) {
            self::$PackageManager = new PackageManager();
        }

        return self::$PackageManager;
    }

    /**
     * Returns the ErrorHandler
     */
    public static function getErrorHandler(): QUI\Exceptions\Handler
    {
        if (self::$ErrorHandler === null) {
            require_once dirname(__FILE__, 2) . '/QUI/Exceptions/Handler.php';

            self::$ErrorHandler = new QUI\Exceptions\Handler();

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
     * Return the mail manager
     */
    public static function getMailManager(): MailManager
    {
        if (self::$MailManager === null) {
            self::$MailManager = new MailManager();
        }

        return self::$MailManager;
    }

    /**
     * Returns the globals Events object
     */
    public static function getEvents(): EventsManager
    {
        if (self::$Events === null) {
            self::$Events = new EventsManager();
        }

        return self::$Events;
    }

    /**
     * Starts the Setup
     *
     * @throws QUI\Exception
     */
    public static function setup(): void
    {
        QUI\Setup::all();
    }

    public static function backendGuiConfigs(): array
    {
        $config = [];
        $config['globals'] = QUI::conf('globals');
        $config['gui'] = QUI::conf('gui');
        $config['permissions'] = QUI::conf('permissions');

        unset($config['globals']['salt']);
        unset($config['globals']['saltlength']);
        unset($config['globals']['rootuser']);

        unset($config['globals']['cms_dir']);
        unset($config['globals']['var_dir']);
        unset($config['globals']['usr_dir']);
        unset($config['globals']['opt_dir']);

        return $config;
    }

    /**
     * Return the QUIQQER version
     */
    public static function version(): string
    {
        return self::getPackageManager()->getVersion();
    }

    /**
     * Get registered vhosts
     */
    public static function vhosts(): array
    {
        if (self::$vhosts !== null) {
            return self::$vhosts;
        }

        try {
            $vhosts = self::getConfig('etc/vhosts.ini.php');
            self::$vhosts = $vhosts->toArray();
        } catch (Exception) {
            self::$vhosts = [];
        }

        return self::$vhosts;
    }

    /**
     * Return the global ajax object
     */
    public static function getAjax(): Ajax
    {
        if (self::$Ajax === null) {
            self::$Ajax = new QUI\Ajax([
                'db_errors' => self::conf('error', 'mysql_ajax_errors_backend')
            ]);
        }

        return self::$Ajax;
    }

    /**
     * Return the table name with the QUI Prefix
     *
     *
     */
    public static function getDBTableName(string $table): string
    {
        return QUI_DB_PRFX . $table;
    }

    /**
     * Return the table name with the QUI Prefix and table params
     *
     * @param boolean $lang - language in the table name? default = true
     *
     */
    public static function getDBProjectTableName(
        string $table,
        Project $Project,
        bool $lang = true
    ): string {
        if ($lang === false) {
            return QUI_DB_PRFX . $Project->getName() . '_' . $table;
        }

        return QUI_DB_PRFX . $Project->getName() . '_' . $Project->getLang() . '_' . $table;
    }

    /**
     * Returns the Country Manager
     */
    public static function getCountries(): CountriesManager
    {
        if (self::$Countries === null) {
            self::$Countries = new CountriesManager();
        }

        return self::$Countries;
    }

    /**
     * Returns the database Object (old version)
     *
     * @deprecated
     * use getDataBase and PDO or direct getPDO
     */
    public static function getDB(): MyDB
    {
        if (self::$DataBase === null) {
            self::$DataBase = new MyDB();
        }

        return self::$DataBase;
    }

    /**
     * Returns the PDO Database object
     *
     * @throws Exception
     */
    public static function getPDO(): PDO
    {
        if (QUI::getDataBase()->getPDO()) {
            return QUI::getDataBase()->getPDO();
        }

        try {
            $Native = self::getDataBaseConnection()->getNativeConnection();

            if ($Native instanceof PDO) {
                return $Native;
            }
        } catch (Doctrine\DBAL\Exception $e) {
            Log::addError($e->getMessage());
        }

        throw new QUI\Exception('PDO not found');
    }

    /**
     * Returns the Database object
     *
     * @deprecated
     */
    public static function getDataBase(): DB
    {
        if (!(self::$DataBase2 instanceof DB)) {
            self::$DataBase2 = new DB([
                'doctrine' => self::getDataBaseConnection()
            ]);
        }

        return self::$DataBase2;
    }

    /**
     * Returns a Project
     * It uses the \QUI\Projects\Manager
     *
     * You can also use \QUI\Projects\Manager::getProject()
     *
     * @param array|string $project - Project name | array('name' => , 'lang' => , 'template' => )
     * @param boolean|string $lang - Project lang (optional)
     * @param boolean|string $template - Project template (optional)
     *
     * @throws QUI\Exception
     */
    public static function getProject(
        array|string $project,
        bool|string $lang = false,
        bool|string $template = false
    ): Project {
        if (is_array($project)) {
            $lang = false;
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

        return ProjectManager::getProject($project, $lang, $template);
    }

    /**
     * Returns the group manager
     */
    public static function getGroups(): GroupManager
    {
        if (self::$Groups === null) {
            self::$Groups = new GroupManager();
        }

        return self::$Groups;
    }

    /**
     * Returns the QUIQQER message handler object
     */
    public static function getMessagesHandler(): Messages
    {
        if (self::$MessageHandler === null) {
            self::$MessageHandler = new Messages();
        }

        return self::$MessageHandler;
    }

    /**
     * Returns the main locale object
     */
    public static function getLocale(): QUI\Locale
    {
        if (self::$Locale === null) {
            self::$Locale = new \QUI\Locale();

            $language = self::conf('globals', 'standardLanguage');
            $languages = self::availableLanguages();

            if (
                isset($_REQUEST['lang'])
                && is_string($_REQUEST['lang'])
                && strlen($_REQUEST['lang']) === 2
            ) {
                self::$Locale->setCurrent($_REQUEST['lang']);
            } elseif (!empty($language)) {
                self::$Locale->setCurrent($language);
            } elseif (count($languages) === 1) {
                self::$Locale->setCurrent($languages[0]);
            }
        }

        if (self::$Locale->getCurrent() === '') {
            $language = self::conf('globals', 'standardLanguage');

            if (!empty($language)) {
                self::$Locale->setCurrent($language);
            }
        }

        return self::$Locale;
    }

    /**
     * Returns all available languages
     */
    public static function availableLanguages(): array
    {
        $languages = QUI\Translator::getAvailableLanguages();

        if (empty($languages)) {
            $languages = ['en'];
        }

        return $languages;
    }

    /**
     * Return the QUIQQER default language locale
     */
    public static function getSystemLocale(): QUI\Locale
    {
        if (self::$SystemLocale !== null) {
            return self::$SystemLocale;
        }

        self::$SystemLocale = new QUI\Locale();
        $language = self::conf('globals', 'standardLanguage');

        if (!empty($language)) {
            self::$SystemLocale->setCurrent($language);
        }

        return self::$SystemLocale;
    }

    /**
     * Returns the wanted package
     *
     * @param string $package - name of the package eq: quiqqer/blog or quiqqer/core
     *
     * @throws QUI\Exception
     */
    public static function getPackage(string $package): Package
    {
        return self::getPackageManager()->getInstalledPackage($package);
    }

    /**
     * Returns the project manager
     */
    public static function getProjectManager(): ProjectManager
    {
        if (self::$ProjectManager === null) {
            self::$ProjectManager = new ProjectManager();
        }

        return self::$ProjectManager;
    }

    /**
     * returns the rewrite object
     */
    public static function getRewrite(): Rewrite
    {
        if (self::$Rewrite === null) {
            self::$Rewrite = new Rewrite();
        }

        return self::$Rewrite;
    }

    /**
     * Return the rights object
     */
    public static function getPermissionManager(): PermissionManager
    {
        if (self::$Rights === null) {
            self::$Rights = new PermissionManager();
        }

        return self::$Rights;
    }

    public static function getGlobalResponse(): Response
    {
        if (self::$Response === null) {
            self::$Response = new Response();

            $Headers = new Headers(self::$Response);
            $Headers->compile();
        }

        return self::$Response;
    }

    /**
     * Return the global QUI Session
     */
    public static function getSession(): Session|QUI\System\Console\Session|null
    {
        if (php_sapi_name() === 'cli') {
            if (self::$Session === null) {
                self::$Session = new QUI\System\Console\Session();
            }

            return self::$Session;
        }

        if (self::$Session === null) {
            self::$Session = new Session();
            self::getRequest()->setSession(self::$Session->getSymfonySession());
        }

        return self::$Session;
    }

    /**
     * Return the global request object
     */
    public static function getRequest(): Request
    {
        if (self::$Request === null) {
            self::$Request = Request::createFromGlobals();
        }

        return self::$Request;
    }

    /**
     * Return the temp manager
     */
    public static function getTemp(): Temp
    {
        if (self::$Temp === null) {
            self::$Temp = new Temp(VAR_DIR . 'tmp');
        }

        return self::$Temp;
    }

    /**
     * Return the Template Manager
     */
    public static function getTemplateManager(): Template
    {
        if (self::$Template === null) {
            self::$Template = new Template();
        }

        return self::$Template;
    }

    /**
     * Get current logged in user
     */
    public static function getUserBySession(): User
    {
        return self::getUsers()->getUserBySession();
    }

    /**
     * Return the user manager
     */
    public static function getUsers(): UserManager
    {
        if (self::$Users === null) {
            self::$Users = new UserManager();
        }

        return self::$Users;
    }

    /**
     * Runs QUIQQER in the backend?
     */
    public static function isBackend(): bool
    {
        return defined('QUIQQER_BACKEND') && QUIQQER_BACKEND;
    }

    /**
     * Runs QUIQQER in the frontend?
     */
    public static function isFrontend(): bool
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
    public static function isSystem(): bool
    {
        return defined('QUIQQER_CONSOLE') && QUIQQER_CONSOLE;
    }

    //region Doctrine

    /**
     * Returns the doctrine DBAL Connection Object
     */
    public static function getDataBaseConnection(): Connection
    {
        if (!(self::$QueryBuilder instanceof Connection)) {
            self::$QueryBuilder = Doctrine\DBAL\DriverManager::getConnection([
                'dbname' => self::conf('db', 'database'),
                'driver' => 'pdo_' . self::conf('db', 'driver'),
                'host' => self::conf('db', 'host'),
                'user' => self::conf('db', 'user'),
                'password' => self::conf('db', 'password')
            ]);
        }

        return self::$QueryBuilder;
    }

    /**
     * Returns a doctrine query builder
     */
    public static function getQueryBuilder(): QueryBuilder
    {
        return self::getDataBaseConnection()->createQueryBuilder();
    }

    /**
     * Returns a doctrine schema manager
     *
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getSchemaManager(): AbstractSchemaManager
    {
        return self::getDataBaseConnection()->createSchemaManager();
    }

    //endregion

    /**
     * Check if runtime cache is enabled globally (default: true).
     *
     * All modules that use runtime caches for instances or data that could possibly
     * cause a memory overflow should recognize this flag and only use their
     * runtime cache if this method returns true.
     */
    public static function isRuntimeCacheEnabled(): bool
    {
        return self::$runtimeCacheEnabled;
    }

    public static function disableRuntimeCache(): void
    {
        self::$runtimeCacheEnabled = false;
    }
}
