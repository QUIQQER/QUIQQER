<?php

/**
 * This file contains the \QUI\Update class
 */

namespace QUI;

use Composer\Script\Event;

use QUI;
use QUI\Utils\System\File as QUIFile;
use QUI\System\Log;
use QUI\Utils\Text\XML;

if (!function_exists('glob_recursive')) {
    /**
     * polyfill for glob_recursive
     * Does not support flag GLOB_BRACE
     *
     * @param $pattern
     * @param int $flags
     * @return array
     */
    function glob_recursive($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge(
                $files,
                glob_recursive($dir.'/'.basename($pattern), $flags)
            );
        }

        return $files;
    }
}

/**
 * Update from QUIQQER
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @todo Backup vor dem Einspielen machen
 */
class Update
{
    /**
     * If a plugin / package would be installed via composer
     *
     * @param Event $Event
     *
     * @todo implement the installation
     */
    public static function onInstall(Event $Event)
    {
        // Log::writeRecursive( $event, 'error' );

        $IO = $Event->getIO();

        QUI::load();

        $IO->write('QUI\Update->onInstall');
        $IO->write(CMS_DIR);
    }

    /**
     * If a plugin / package is updated via composer
     *
     * @param Event $Event
     *
     * @throws QUI\Exception
     */
    public static function onUpdate(Event $Event)
    {
        $IO       = $Event->getIO();
        $Composer = $Event->getComposer();

        if (!defined('ETC_DIR')) {
            define('ETC_DIR', $Composer->getConfig()->get('quiqqer-dir').'etc/');
        }

        // load quiqqer
        QUI::load();

        if (!defined('URL_LIB_DIR')) {
            define('URL_LIB_DIR', QUI::conf('globals', 'url_lib_dir'));
        }

        if (!defined('URL_BIN_DIR')) {
            define('URL_BIN_DIR', QUI::conf('globals', 'url_bin_dir'));
        }

        if (!defined('URL_SYS_DIR')) {
            define('URL_SYS_DIR', QUI::conf('globals', 'url_sys_dir'));
        }

        if (!defined('URL_OPT_DIR')) {
            define('URL_OPT_DIR', URL_DIR.str_replace(CMS_DIR, '', OPT_DIR));
        }

        if (!defined('URL_USR_DIR')) {
            define('URL_USR_DIR', URL_DIR.str_replace(CMS_DIR, '', USR_DIR));
        }

        if (!defined('URL_VAR_DIR')) {
            define('URL_VAR_DIR', URL_DIR.str_replace(CMS_DIR, '', VAR_DIR));
        }


        QUI::getLocale()->setCurrent('en');

        // session table
        QUI::getSession()->setup();

        // rights setup, so we have all importend tables
        QUI\Permissions\Manager::setup();

        // WYSIWYG Setup
        QUI\Editor\Manager::setup();

        // Events setup
        QUI\Events\Manager::setup();
        QUI\Events\Manager::clear();

        QUI\Messages\Handler::setup();

        $packages_dir = $Composer->getConfig()->get('vendor-dir');

        if (defined('OPT_DIR')) {
            $packages_dir = OPT_DIR;
        }

        $packages = QUIFile::readDir($packages_dir);

        $IO->write('Start QUIQQER updating ...');

        // first we need all databases
        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $package_dir = $packages_dir.'/'.$package;
            $list        = QUIFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir.'/'.$sub)) {
                    continue;
                }

                // database setup
                self::importDatabase(
                    $package_dir.'/'.$sub.'/database.xml',
                    $IO
                );
            }
        }

        // than we need translations
        self::importAllLocaleXMLs($Composer);


        // compile the translations
        // so the new translations are available
        $IO->write('Execute QUIQQER Translator');

        QUI\Translator::create();


        // then we can read the rest xml files
        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $package_dir = $packages_dir.'/'.$package;
            $list        = QUIFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir.'/'.$sub)) {
                    continue;
                }

                // register template engines, if exist in a package
                self::importTemplateEngines(
                    $package_dir.'/'.$sub.'/engines.xml',
                    $IO
                );

                // register wysiwyg editors
                self::importEditors(
                    $package_dir.'/'.$sub.'/wysiwyg.xml',
                    $IO
                );

                // register menu entries
                self::importMenu(
                    $package_dir.'/'.$sub.'/menu.xml',
                    $IO
                );

                // permissions
                self::importPermissions(
                    $package_dir.'/'.$sub.'/permissions.xml',
                    $sub,
                    $IO
                );

                // events
                self::importEvents(
                    $package_dir.'/'.$sub.'/events.xml',
                    $package.'/'.$sub
                );
            }
        }

        // permissions
        self::importPermissions(
            CMS_DIR.'/admin/permissions.xml',
            'system',
            $IO
        );


        $IO->write('QUIQQER Update finish');

        // quiqqer setup
        $IO->write('Starting QUIQQER setup');

        if (QUI::getUserBySession()->getId()) {
            QUI::setup();
            $IO->write('QUIQQER Setup finish');
        } else {
            QUI\Cache\Manager::clearAll();
            $IO->write('Maybe some Databases or Plugins need a setup. Please log in and execute the setup.');
        }
    }

    /**
     * Import / register the template engines in an xml file and register it
     *
     * @param string $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     *
     * @throws QUI\Exception
     */
    public static function importTemplateEngines($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        $engines = XML::getTemplateEnginesFromXml($xml_file);

        foreach ($engines as $Engine) {
            /* @var $Engine \DOMElement */
            if (!$Engine->getAttribute('class_name')
                || empty($Engine->nodeValue)
            ) {
                continue;
            }

            QUI::getTemplateManager()->registerEngine(
                trim($Engine->nodeValue),
                $Engine->getAttribute('class_name')
            );
        }
    }

    /**
     * Import / register the wysiwyg editors
     *
     * @param string $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    public static function importEditors($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        $editors = XML::getWysiwygEditorsFromXml($xml_file);

        foreach ($editors as $Editor) {
            /* @var $Editor \DOMElement */
            if (!$Editor->getAttribute('package')
                || empty($Editor->nodeValue)
            ) {
                continue;
            }

            QUI\Editor\Manager::registerEditor(
                trim($Editor->nodeValue),
                $Editor->getAttribute('package')
            );
        }
    }

    /**
     * Import / register quiqqer events
     *
     * @param string $xml_file - path to an engine.xml
     * @param string $packageName - optional, Name of the package
     */
    public static function importEvents($xml_file, $packageName = '')
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        $events = XML::getEventsFromXml($xml_file);
        $Events = QUI::getEvents();

        foreach ($events as $Event) {
            /* @var $Event \DOMElement */
            if (!$Event->getAttribute('on')
                || !$Event->getAttribute('fire')
            ) {
                continue;
            }

            $priority = 0;

            if ($Event->getAttribute('priority')) {
                $priority = (int)$Event->getAttribute('priority');
            }

            $Events->addEvent(
                $Event->getAttribute('on'),
                $Event->getAttribute('fire'),
                $packageName,
                $priority
            );
        }
    }

    /**
     * Import / register quiqqer site events
     *
     * @param string $xml_file - path to an engine.xml
     * @param $IO - (optional)  Composer InputOutput
     */
    public static function importSiteEvents($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        $events = XML::getSiteEventsFromXml($xml_file);
        $Events = QUI::getEvents();

        foreach ($events as $event) {
            $Events->addSiteEvent($event['on'], $event['fire'], $event['type']);
        }
    }

    /**
     * Import / register the menu items
     * it create a cache file for the package
     *
     * @param string $xml_file - path to an engine.xml
     * @param $IO - Composer InputOutput
     */
    public static function importMenu($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        $items = XML::getMenuItemsXml($xml_file);

        if (!count($items)) {
            return;
        }

        $file = str_replace(
            array(CMS_DIR, '/'),
            array('', '_'),
            $xml_file
        );

        $dir      = VAR_DIR.'cache/menu/';
        $cachfile = $dir.$file;

        QUIFile::mkdir($dir);

        if (file_exists($cachfile)) {
            unlink($cachfile);
        }

        file_put_contents($cachfile, file_get_contents($xml_file));
    }

    /**
     * Database setup
     * Reads the database.xml and create the definit tables
     *
     * @param string $xml_file - path to an database.xml
     * @param $IO - Composer InputOutput
     *
     * @throws QUI\Exception
     */
    public static function importDatabase($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        XML::importDataBaseFromXml($xml_file);
    }

    /**
     * Locale setup - translations
     * Reads the locale.xml and import it
     *
     * @param string $xml_file - path to an locale.xml
     * @param $IO - Composer InputOutput
     *
     * @throws QUI\Exception
     */
    public static function importLocale($xml_file, $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        QUI\Translator::import($xml_file, true, true);
    }

    /**
     * Permissions import
     * Reads the permissions.xml and import it
     *
     * @param string $xml_file - path to an locale.xml
     * @param string $src - Source for the permissions
     * @param $IO - Composer InputOutput
     */
    public static function importPermissions($xml_file, $src = '', $IO = null)
    {
        if (!file_exists($xml_file)) {
            return;
        }

        Log::addDebug('Read: '.$xml_file);

        XML::importPermissionsFromXml($xml_file, $src);
    }

    /**
     * Reimportation from all menu.xml files
     * Read all packages and import the menu.xml files to the quiqqer system
     *
     * @param \Composer\Composer $Composer - optional
     *
     * @throws QUI\Exception
     * @deprecated
     */
    public static function importAllMenuXMLs($Composer = null)
    {
        $packages_dir = false;

        if ($Composer) {
            $packages_dir = $Composer->getConfig()->get('vendor-dir');
        }

        if (defined('OPT_DIR')) {
            $packages_dir = OPT_DIR;
        }

        if (!$packages_dir) {
            throw new QUI\Exception(
                'Could not import menu.xml. Package-Dir not found'
            );
        }

        $packages = QUIFile::readDir(OPT_DIR);

        // then we can read the rest xml files
        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $Package = QUI::getPackage($package);

            if (!$Package->hasPermission()) {
                continue;
            }

            // @todo in Paket Klasse integrieren
            $package_dir = OPT_DIR.'/'.$package;
            $list        = QUIFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir.'/'.$sub)) {
                    continue;
                }

                // register menu entries
                self::importMenu(
                    $package_dir.'/'.$sub.'/menu.xml'
                );
            }
        }
    }

    /**
     * Reimportation from all permissions.xml files
     * Read all packages and import the permissions.xml files to the quiqqer system
     */
    public static function importAllPermissionsXMLs()
    {
        $packages = QUIFile::readDir(OPT_DIR);

        // clear system permissions
        QUI::getDataBase()->delete(
            QUI::getDBTableName(QUI\Permissions\Manager::TABLE),
            array(
                'src' => array(
                    'type'  => 'NOT',
                    'value' => 'user'
                )
            )
        );

        QUI::$Rights = null; // so we have no permission cache


        self::importPermissions(
            CMS_DIR.'/admin/permissions.xml',
            'system'
        );

        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $package_dir = OPT_DIR.'/'.$package;
            $list        = QUIFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir.'/'.$sub)) {
                    continue;
                }

                // register permissions entries
                self::importPermissions(
                    $package_dir.'/'.$sub.'/permissions.xml',
                    $sub
                );
            }
        }
    }

    /**
     * Reimportation from all locale.xml files
     *
     * @param \Composer\Composer $Composer - optional
     *
     * @throws QUI\Exception
     */
    public static function importAllLocaleXMLs($Composer = null)
    {
        $packages_dir = false;

        if ($Composer) {
            $packages_dir = $Composer->getConfig()->get('vendor-dir');
        }

        if (defined('OPT_DIR')) {
            $packages_dir = OPT_DIR;
        }

        if (!$packages_dir) {
            throw new QUI\Exception(
                'Could not import menu.xml. Package-Dir not found'
            );
        }

        $packages = QUIFile::readDir($packages_dir);

        foreach ($packages as $package) {
            if ($package == 'composer') {
                continue;
            }

            $package_dir = $packages_dir.'/'.$package;
            $list        = QUIFile::readDir($package_dir);

            foreach ($list as $sub) {
                if (!is_dir($package_dir.'/'.$sub)) {
                    continue;
                }

                // locale setup
                self::importLocale(
                    $package_dir.'/'.$sub.'/locale.xml'
                );
            }
        }

        // projects
        $projects = QUI::getProjectManager()->getProjects();

        foreach ($projects as $project) {
            // locale setup
            self::importLocale(
                USR_DIR.$project.'/locale.xml'
            );
        }

        // system xmls
        $File       = new QUIFile();
        $locale_dir = CMS_DIR.'admin/locale/';
        $locales    = $File->readDirRecursiv($locale_dir, true);

        foreach ($locales as $locale) {
            self::importLocale($locale_dir.$locale);
        }


        // javascript
        $list = QUI\Utils\System\File::find(BIN_DIR.'QUI/', '*.xml');

        foreach ($list as $file) {
            self::importLocale(trim($file));
        }

        // lib
        $list = QUI\Utils\System\File::find(LIB_DIR.'xml/locale/', '*.xml');

        foreach ($list as $file) {
            self::importLocale(trim($file));
        }

        // admin templates
        $list = QUI\Utils\System\File::find(SYS_DIR.'template/', '*.xml');

        foreach ($list as $file) {
            self::importLocale(trim($file));
        }
    }
}
