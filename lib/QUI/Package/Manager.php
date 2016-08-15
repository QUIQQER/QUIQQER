<?php

/**
 * This file contains \QUI\Package\Manager
 */

namespace QUI\Package;

// Use the Composer classes
if (!defined('STDIN')) {
    define('STDIN', fopen("php://stdin", "r"));
}

if (!defined('JSON_UNESCAPED_SLASHES')) {
    define('JSON_UNESCAPED_SLASHES', 64);
}

if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 256);
}


use Composer\Console\Application;
use Composer\Package\Package;
use QUI\Utils\System\File;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

use QUI;
use QUI\Utils\System\File as QUIFile;

/**
 * Package Manager for the QUIQQER System
 *
 * Sorry, the package manager is little bit complicated
 * when the time is right, i think i must make it clearer
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @event   onOutput [ string $message ]
 */
class Manager extends QUI\QDOM
{
    const CACHE_NAME_TYPES = 'qui/packages/types';

    /**
     * Package Directory
     *
     * @var string
     */
    protected $dir;

    /**
     * VAR Directory for composer
     * eq: here are the cache and the quiqqer composer.json file
     *
     * @var string
     */
    protected $vardir;

    /**
     * Path to the composer.json file
     *
     * @var string
     */
    protected $composer_json;

    /**
     * Path to the composer.lock file
     *
     * @var string
     */
    protected $composer_lock;

    /**
     * exec command to the composer.phar file
     *
     * @var string
     */
    protected $composer_exec;

    /**
     * Packaglist - installed packages
     *
     * @var array
     */
    protected $list = false;

    /**
     * Can composer execute via bash? shell?
     *
     * @var boolean
     */
    protected $exec = false;

    /**
     * temporary require packages
     *
     * @var array
     */
    protected $require = array();

    /**
     * QUIQQER Version ->getVersion()
     *
     * @var string
     */
    protected $version = null;

    /**
     * List of packages objects
     *
     * @var array
     */
    protected $packages = array();

    /**
     * Composer Application
     *
     * @var Application
     */
    protected $Application;

    /**
     * internal event manager
     *
     * @var QUI\Events\Manager
     */
    public $Events;

    /**
     * Path to the local repository
     *
     * @var string
     */
    protected $localRepository;

    /**
     * active servers - use as temp for local repo using
     *
     * @var array
     */
    protected $activeServers = array();

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        // defaults
        $this->setAttributes(array(
            '--prefer-dist' => true
        ));

        $this->dir    = OPT_DIR; // CMS_DIR .'packages/';
        $this->vardir = VAR_DIR . 'composer/';

        $this->composer_json = $this->vardir . 'composer.json';
        $this->composer_lock = $this->vardir . 'composer.lock';

        $this->Events = new QUI\Events\Manager();
        $this->setAttributes($attributes);
    }

    /**
     * Return the available quiqqer package types
     *
     * @return array
     */
    public static function getPackageTypes()
    {
        return array(
            'quiqqer-library', // deprecated
            'quiqqer-plugin',
            'quiqqer-module',
            'quiqqer-template'
        );
    }

    /**
     * Return the last update date
     *
     * @return integer
     */
    public function getLastUpdateDate()
    {
        return (int)$this->getUpdateConf()->get('quiqqer', 'lastUpdate');
    }

    /**
     * Set the last update date to now
     *
     * @throws QUI\Exception
     */
    public function setLastUpdateDate()
    {
        $Last = $this->getUpdateConf();
        $Last->set('quiqqer', 'lastUpdate', time());
        $Last->save();
    }

    /**
     * Return the Composer Application
     *
     * @return \Composer\Console\Application
     */
    protected function getApplication()
    {
        if ($this->Application) {
            return $this->Application;
        }

        // Create the application and run it with the commands
        $this->Application = new Application();
        $this->Application->setAutoExit(false);

        QUI\Utils\System\File::mkdir($this->vardir);

        putenv("COMPOSER_HOME=" . $this->vardir);

        return $this->Application;
    }

    /**
     * Return the version from the composer json
     *
     * @return string
     */
    public function getVersion()
    {
        if ($this->version) {
            return $this->version;
        }

        if (!file_exists($this->composer_json)) {
            return '';
        }

        $data = file_get_contents($this->composer_json);
        $data = json_decode($data, true);

        if (isset($data['require']['quiqqer/quiqqer'])) {
            $this->version = $data['require']['quiqqer/quiqqer'];
        } else {
            $this->version = $data['version'];
        }

        return $this->version;
    }

    /**
     * Checks if the composer.json exists
     * if not, the system will try to create the composer.json (with all installed packages)
     */
    protected function checkComposer()
    {
        if (file_exists($this->composer_json)) {
            return;
        }

        $this->createComposerJSON();
    }

    /**
     * Create the composer.json file for the system
     */
    protected function createComposerJSON()
    {
        if (file_exists($this->composer_json)) {
            $composerJson = json_decode(
                file_get_contents($this->composer_json)
            );
        } else {
            $template = file_get_contents(
                dirname(__FILE__) . '/composer.tpl'
            );

            $composerJson = json_decode($template);
        }

        // config
        $composerJson->config = array(
            "vendor-dir"    => OPT_DIR,
            "cache-dir"     => $this->vardir,
            "component-dir" => OPT_DIR . 'bin',
            "quiqqer-dir"   => CMS_DIR
        );

        $composerJson->extra = array(
            "asset-installer-paths" => array(
                "npm-asset-library"   => OPT_DIR . 'bin',
                "bower-asset-library" => OPT_DIR . 'bin'
            )
        );


        // make the repository list
        $servers      = $this->getServerList();
        $repositories = array();

        foreach ($servers as $server => $params) {
            if ($server == 'packagist') {
                continue;
            }

            if (!isset($params['active']) || $params['active'] != 1) {
                continue;
            }

            $repositories[] = array(
                'type' => $params['type'],
                'url'  => $server
            );
        }

        if (isset($servers['packagist'])
            && $servers['packagist']['active'] == 0
        ) {
            $repositories[] = array(
                'packagist' => false
            );
        }

        $composerJson->repositories = $repositories;


        // standard require
        if (empty($composerJson->require)) {
            $list = $this->getList();

            // must have
            $require                    = array();
            $require["php"]             = ">=5.3.2";
            $require["quiqqer/quiqqer"] = "dev-master";

            foreach ($list as $package) {
                $require[$package['name']] = $package['version'];
            }

            ksort($require);

            $composerJson->require = $require;
        }


        // save
        file_put_contents($this->composer_json, json_encode(
            $composerJson,
            \JSON_PRETTY_PRINT
        ));
    }

    /**
     * Creates a backup from the composer.json file
     */
    public function createComposerBackup()
    {
        if (!file_exists($this->composer_json)) {
            throw new QUI\Exception(
                'Composer File not found'
            );
        }

        $backupDir = VAR_DIR . 'backup/composer/';

        QUIFile::mkdir($backupDir);

        $date = date('Y-m-d__H-i-s');

        $composerJson = $backupDir . 'composer_' . $date . '.json';
        $composerLock = $backupDir . 'composer_' . $date . '.lock';

        if (file_exists($composerJson) || file_exists($composerLock)) {
            $count = 1;

            while (true) {
                $composerJson = "{$backupDir}composer_{$date}_({$count}).json";
                $composerLock = "{$backupDir}composer_{$date}_({$count}).lock";

                if (file_exists($composerJson)) {
                    $count++;
                    continue;
                }

                if (file_exists($composerJson)) {
                    $count++;
                    continue;
                }

                break;
            }
        }

        QUIFile::copy($this->composer_json, $composerJson);
        QUIFile::copy($this->composer_lock, $composerLock);
    }

    /**
     * Clear the complete composer cache
     */
    public function clearComposerCache()
    {
        QUI::getTemp()->moveToTemp($this->vardir . 'repo/');
        QUI::getTemp()->moveToTemp($this->vardir . 'files/');
    }

    /**
     * Package Methods
     */

    /**
     * Return the composer array
     *
     * @return array
     */
    protected function getComposerJSON()
    {
        $this->checkComposer();

        $json   = file_get_contents($this->composer_json);
        $result = json_decode($json, true);

        return $result;
    }

    /**
     * internal get list method
     * return all installed packages and create the internal package list cache
     *
     * @return array
     */
    protected function getList()
    {
        if ($this->list) {
            return $this->list;
        }

        try {
            $this->list = QUI\Cache\Manager::get(self::CACHE_NAME_TYPES);
            return $this->list;
        } catch (QUI\Exception $Exception) {
        }

        $installed_file = $this->dir . 'composer/installed.json';

        if (!file_exists($installed_file)) {
            return array();
        }

        $data = file_get_contents($installed_file);
        $list = json_decode($data, true);

        $result = array();

        if (is_array($list)) {
            foreach ($list as $entry) {
                if (!isset($entry['type'])) {
                    $result[] = $entry;
                    continue;
                }

                if ($entry['type'] != 'quiqqer-module') {
                    $result[] = $entry;
                    continue;
                }

                $path = OPT_DIR . $entry['name'] . '/';

                if (file_exists($path . 'settings.xml')) {
                    $entry['_settings'] = 1;
                }

                if (file_exists($path . 'permissions.xml')) {
                    $entry['_permissions'] = 1;
                }

                if (file_exists($path . 'database.xml')) {
                    $entry['_database'] = 1;
                }

                $result[] = $entry;
            }

            $this->list = $result;
        }

        QUI\Cache\Manager::set(self::CACHE_NAME_TYPES, $this->list);

        return $this->list;
    }

    /**
     * Refreshed the installed package list
     * If some packages are uploaded, sometimes the package versions and data are not correct
     *
     * this method correct it
     */
    protected function refreshInstalledList()
    {
        $installed_file = $this->dir . 'composer/installed.json';

        if (!file_exists($installed_file)) {
            return;
        }


        $data = file_get_contents($installed_file);
        $list = json_decode($data, true);

        foreach ($list as $key => $entry) {
            $cf = $this->dir . $entry['name'] . '/composer.json';

            if (!file_exists($cf)) {
                continue;
            }

            $data = json_decode(file_get_contents($cf), true);

            if (!is_array($data)) {
                continue;
            }

            if (!isset($data['version'])) {
                continue;
            }

            /*
            $list[ $key ]['version'] = $data['version'];

            // is that right?
            $list[ $key ]["version_normalized"] = str_replace(
                array('x', '*'),
                9999999,
                $data['version']
            );
            */
        }

        $this->list = array();

        if (is_array($list)) {
            $this->list = $list;
        }
    }

    /**
     * Return the installed packages
     *
     * @param array $params - [optional] search / limit params
     *
     * @return array
     */
    public function getInstalled($params = array())
    {
        $list   = $this->getList();
        $result = $list;

        if (isset($params['type'])) {
            $result = array();

            foreach ($list as $package) {
                if (!isset($package['type'])) {
                    continue;
                }

                if (!empty($params['type'])
                    && $params['type'] != $package['type']
                ) {
                    continue;
                }

                $result[] = $package;
            }
        }

        if (isset($params['limit']) && isset($params['page'])) {
            $limit = (int)$params['limit'];
            $page  = (int)$params['page'];

            return QUI\Utils\Grid::getResult($result, $page, $limit);
        }

        return $result;
    }

    /**
     * Return a package object
     *
     * @param string $package - name of the package
     *
     * @return QUI\Package\Package
     * @throws QUI\Exception
     */
    public function getInstalledPackage($package)
    {
        if (!isset($this->packages[$package])) {
            $this->packages[$package] = new QUI\Package\Package($package);
        }

        return $this->packages[$package];
    }

    /**
     * Install Package
     *
     * @param string $package - name of the package
     * @param string|boolean $version - (optional) version of the package default = dev-master
     */
    public function install($package, $version = false)
    {
        QUI\System\Log::addDebug(
            'Install package ' . $package . ' with Lock Client'
        );

        $this->createComposerBackup();
        $this->checkComposer();

        try {
            // update lock file via lock server
            $lockData = $this->getLockClient()->requires($package, $version);

            // update composer.lock
            file_put_contents($this->composer_lock, $lockData);

            // add package to composer.json
            $composer = json_decode(
                file_get_contents($this->composer_json),
                true
            );

            if (!isset($composer['require'][$package])) {
                $composer['require'][$package] = $version ? $version : '*';

                file_put_contents(
                    $this->composer_json,
                    json_encode($composer, \JSON_PRETTY_PRINT)
                );
            }

            QUI\System\Log::addDebug('Execute install command');

            $this->execComposer('install', array(
                '--no-progress' => true,
                '--no-ansi'     => true
            ));
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug('LOCK Server Error');
            QUI\System\Log::addDebug($Exception->getMessage());

            $this->installWithoutLockClient($package, $version);

            return;
        }

        $this->getInstalledPackage($package)->install();

        // execute setup of all packages
        $this->setup($package);
    }

    /**
     * Install Package
     *
     * @param string $package - name of the package
     * @param string|boolean $version - (optional) version of the package
     */
    public function installWithoutLockClient($package, $version = false)
    {
        QUI\System\Log::addDebug(
            'Install package ' . $package . ' without Lock Client'
        );

        if ($version) {
            $this->execComposer('require', array(
                'packages' => array(
                    $package . ':' . $version
                )
            ));
        } else {
            $this->execComposer('require', array(
                'packages' => $package
            ));
        }

        $this->getInstalledPackage($package)->install();

        // execute setup of all packages
        $this->setup($package);
    }

    /**
     * Install only a local package
     *
     * @param  string $package - name of the package
     * @param boolean $version - (optional) version of the package
     */
    public function installLocalPackage($package, $version = false)
    {
        QUI\System\Log::addDebug(
            'Install package ' . $package . ' without Lock Client'
        );

        $this->useOnlyLocalRepository();
        $this->installWithoutLockClient($package, $version);
        $this->resetRepositories();
    }

    /**
     * Add a Package to the composer json
     *
     * @param string|array $package - name of the package
     * @param string|boolean $version - (optional) version of the package
     */
    public function setPackage($package, $version = false)
    {
        if (!$version) {
            $version = 'dev-master';
        }

        $json    = $this->getComposerJSON();
        $quiqqer = false;

        if (is_array($package)) {
            foreach ($package as $pkg) {
                $json['require'][$pkg] = $version;

                if ($pkg == 'quiqer/quiqqer') {
                    $quiqqer = true;
                }
            }
        } else {
            $json['require'][$package] = $version;

            if ($package == 'quiqer/quiqqer') {
                $quiqqer = true;
            }
        }


        // minimum-stability
        if ($quiqqer && $version == 'dev-dev') {
            $json['minimum-stability'] = 'dev';
        } else {
            if ($quiqqer) {
                $json['minimum-stability'] = 'stable';
            }
        }


        $json = json_encode($json, \JSON_PRETTY_PRINT);

        if (file_exists($this->composer_json)) {
            unlink($this->composer_json);
        }

        file_put_contents($this->composer_json, $json);
    }

    /**
     * Return the params of an installed package
     * If you want the Package Object, you should use getInstalledPackage
     *
     * @param string $package
     *
     * @return array
     */
    public function getPackage($package)
    {
        $cache = 'packages/cache/info/' . $package;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        $list   = $this->getList();
        $result = array();

        foreach ($list as $pkg) {
            if (!isset($pkg['name'])) {
                continue;
            }

            if ($pkg['name'] == $package) {
                $pkg['dependencies'] = $this->getDependencies($package);
                $result              = $pkg;
                break;
            }
        }

        $showData = $this->show($package);

        if (isset($showData['versions'])) {
            $result['versions'] = $showData['versions'];
        }

        if (isset($showData['require'])) {
            $result['require'] = $showData['require'];
        }

        QUI\Cache\Manager::set($cache, $result, 3600);

        return $result;
    }

    /**
     * Return the dependencies of a package
     *
     * @param string $package - package name
     *
     * @return array - list of dependencies
     */
    public function getDependencies($package)
    {
        $list   = $this->getList();
        $result = array();

        foreach ($list as $pkg) {
            if (!isset($pkg['require']) || empty($pkg['require'])) {
                continue;
            }

            if (isset($pkg['require'][$package])) {
                $result[] = $pkg['name'];
            }
        }

        return $result;
    }

    /**
     * Return package details
     *
     * @param string $package
     *
     * @return array
     */
    public function show($package)
    {
        $cache = 'packages/cache/show/' . $package;

        try {
            return QUI\Cache\Manager::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        $this->checkComposer();

        $result = array();

        $show = $this->execComposer('show', array(
            'package' => $package
        ), true);

        foreach ($show as $k => $line) {
            if (strpos($line, '<info>') === false) {
                continue;
            }

            if (strpos($line, ':') === false) {
                continue;
            }

            $line  = explode(':', $line);
            $key   = trim(strip_tags($line[0]));
            $value = trim(strip_tags($line[1]));

            if ($key == 'versions') {
                $value = array_map('trim', explode(',', $value));
            }

            if ($key == 'descrip.') {
                $key = 'description';
            }

            if ($line == 'requires') {
                $_temp             = $show;
                $result['require'] = array_slice($_temp, $k + 1);

                continue;
            }

            $result[$key] = $value;
        }

        QUI\Cache\Manager::set($cache, $result, 3600);

        return $result;
    }

    /**
     * Search a string in the repository
     *
     * @param string $str - search string
     *
     * @return array
     */
    public function searchPackage($str)
    {
        $result = array();
        $str    = QUI\Utils\Security\Orthos::clearShell($str);

        $list = $this->execComposer('search', array(
            'tokens' => array($str)
        ));

        foreach ($list as $entry) {
            $expl = explode(' ', $entry, 2);

            if (isset($expl[0]) && isset($expl[1])) {
                $result[$expl[0]] = $expl[1];
            }
        }

        return $result;
    }

    /**
     * Execute a setup for a package
     *
     * @param string $package
     */
    public function setup($package)
    {
        QUIFile::mkdir(CMS_DIR . 'etc/plugins/');

        try {
            $Package = $this->getInstalledPackage($package);
            $Package->setup();
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_WARNING);
        }
    }

    /**
     * Update Server Methods
     */

    /**
     * Refresh the server list in the var dir
     */
    public function refreshServerList()
    {
        $this->createComposerJSON();
    }

    /**
     * Return the server list
     *
     * @return array
     */
    public function getServerList()
    {
        try {
            return QUI::getConfig('etc/source.list.ini.php')->toArray();
        } catch (QUI\Exception $Exception) {
        }

        return array();
    }

    /**
     * Activate or Deactivate a server
     *
     * @param string $server - Server, IP, Host
     * @param boolean $status - 1 = active, 0 = disabled
     * @param boolean $backup - Optional (default=true, create a backup, false = create no backup
     */
    public function setServerStatus($server, $status, $backup = true)
    {
        $Config = QUI::getConfig('etc/source.list.ini.php');
        $status = (bool)$status ? 1 : 0;

        $Config->setValue($server, 'active', $status);
        $Config->save();

        if ($backup) {
            $this->createComposerBackup();
        }

        $this->createComposerJSON();
    }

    /**
     * Add a server to the update-server list
     *
     * @param string $server - Server, IP, Host
     * @param array $params - Server Parameter
     */
    public function addServer($server, $params = array())
    {
        if (empty($server)) {
            return;
        }

        if (!is_array($params)) {
            return;
        }

        $this->createComposerBackup();

        $Config = QUI::getConfig('etc/source.list.ini.php');
        $Config->setValue($server, 'active', 0);

        if (isset($params['type'])) {
            switch ($params['type']) {
                case "composer":
                case "vcs":
                case "pear":
                case "package":
                case "artifact":
                    $Config->setValue($server, 'type', $params['type']);
                    break;
            }
        }

        $Config->save();

        $this->refreshServerList();
    }

    /**
     * Remove a Server completly from the update-server list
     *
     * @param string|array $server
     */
    public function removeServer($server)
    {
        $Config = QUI::getConfig('etc/source.list.ini.php');

        if (is_array($server)) {
            foreach ($server as $entry) {
                $Config->del($entry);
            }
        } else {
            $Config->del($server);
        }

        $Config->save();

        $this->createComposerBackup();
        $this->refreshServerList();
    }

    /**
     * Update methods
     */

    /**
     * Check for updates
     *
     * @throws \QUI\Exception
     */
    public function checkUpdates()
    {
        $this->checkComposer();

        $packages = array();

        try {
            $LockClient = $this->getLockClient();

            return $LockClient->dryUpdate();
        } catch (QUI\Exception $Exception) {
        }


        // error at lock server
        $result = $this->execComposer('update', array(
            '--dry-run' => true
        ));


        QUI\System\Log::addDebug(print_r($result, true));

        foreach ($result as $line) {
            if (strpos($line, '-') === false
                || strpos($line, '/') === false
                || strpos($line, '(') === false
            ) {
                continue;
            }

            if (strpos($line, 'Installing') !== false) {
                preg_match('#Installing ([^ ]*) #i', $line, $package);
            } else {
                preg_match('#Updating ([^ ]*) #i', $line, $package);
            }

            preg_match_all('#\(([^\)]*)\)#', $line, $versions);

            if (isset($package[1])) {
                $package = $package[1];
            }

            $from = '';
            $to   = '';

            if (isset($versions[1])) {
                if (isset($versions[1][0])) {
                    $from = $versions[1][0];
                    $to   = $versions[1][0]; // if "to" isn't set
                }

                if (isset($versions[1][1])) {
                    $to = $versions[1][1];
                }
            }

            $packages[] = array(
                'package' => $package,
                'from'    => $from,
                'to'      => $to
            );
        }

        return $packages;
    }

    /**
     * Update a package or the entire system
     *
     * @param string|boolean $package - optional, package name, if false, it updates the complete system
     *
     * @throws QUI\Exception
     *
     * @todo if exception uncommited changes -> own error message
     * @todo if exception uncommited changes -> interactive mode
     */
    public function update($package = false)
    {
        $this->createComposerBackup();


        if (is_string($package) && empty($package)) {
            $package = false;
        }

        if (!is_string($package) && !is_bool($package)) {
            $package = false;
        }


        try {
            QUI\System\Log::addDebug('LOCK Server used');

            $lockData = $this->getLockClient()->update($package);

            // update composer.lock
            file_put_contents($this->composer_lock, $lockData);

            QUI\System\Log::addDebug('LOCK Server done');

            $output = $this->execComposer('install', array(
                '--no-progress' => true,
                '--no-ansi'     => true
            ));
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug('LOCK Server Error');
            QUI\System\Log::addDebug($Exception->getMessage());

            if ($package) {
                $output = $this->execComposer('update', array(
                    'packages'      => array($package),
                    '--no-progress' => true,
                    '--no-ansi'     => true
                ));
            } else {
                $output = $this->execComposer('update', array(
                    '--no-progress' => true,
                    '--no-ansi'     => true
                ));
            }
        }


        // exception?
        foreach ($output as $key => $msg) {
            if (!is_string($package)) {
                continue;
            }

            $msg = trim($msg);

            // if not installed
            if (strpos($msg, $package) !== false
                && strpos($msg, 'not installed') !== false
            ) {
                $this->install($package);
            }

            if (strpos($msg, 'Exception')) {
                throw new QUI\Exception(
                    $output[$key + 1]
                );
            }
        }

        QUI\System\Log::addDebug(implode("\n", $output));

        // composer optimize
        $optimize = $this->execComposer('dump-autoload', array(
            '--optimize' => true
        ));

        // set last update
        $Last = $this->getUpdateConf();
        $Last->set('quiqqer', 'lastUpdate', time());
        $Last->save();

        QUI\System\Log::addDebug(implode("\n", $optimize));
    }

    /**
     * Returns the update config object
     *
     * @return QUI\Config
     */
    protected function getUpdateConf()
    {
        // set last update
        if (!file_exists(CMS_DIR . 'etc/last_update.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/last_update.ini.php', '');
        }

        return new QUI\Config(CMS_DIR . 'etc/last_update.ini.php');
    }

    /**
     * Return the lock client with the settings
     *
     * @return QUI\Lockserver\Client
     */
    protected function getLockClient()
    {
        return new QUI\Lockserver\Client(array(
            'composerJsonFile' => $this->composer_json,
            'composerLockFile' => $this->composer_lock
        ));
    }

    /**
     * activate the locale repository,
     * if the repository is not in the server list, the repository would be added
     */
    public function activateLocalServer()
    {
        $serverDir = $this->getUploadPackageDir();

        $this->addServer($serverDir, array(
            "type" => "artifact"
        ));

        $this->setServerStatus($serverDir, 1);
    }

    /**
     * Update a package or the entire system from a package archive
     *
     * @param string|boolean $package - Name of the package
     *
     * @throws QUI\Exception
     */
    public function updateWithLocalRepository($package = false)
    {
        // backup
        $this->createComposerBackup();
        $this->useOnlyLocalRepository();

        // execute update
        $this->update($package);

        $this->resetRepositories();
    }

    /**
     * use only the local repository
     *
     * @return array
     */
    protected function useOnlyLocalRepository()
    {
        // deactivate active servers
        $activeServers = array();
        $serverList    = $this->getServerList();

        foreach ($serverList as $server => $data) {
            if ($data['active'] == 1) {
                $activeServers[] = $server;
            }
        }

        foreach ($activeServers as $server) {
            $this->setServerStatus($server, 0, false);
        }

        // activate local repos
        $this->activateLocalServer();
        $this->createComposerJSON();

        $this->activeServers = $activeServers;
    }

    /**
     * reset the repositories after only local repo using
     */
    protected function resetRepositories()
    {
        // activate active servers
        foreach ($this->activeServers as $server) {
            $this->setServerStatus($server, 1, false);
        }

        $this->createComposerJSON();
    }

    /**
     * Execute a composer command
     *
     * @param string $command - composer command
     * @param array $params - composer argument params
     * @param boolean $showInfo - standard = false; shows messages with <info> or not
     *
     * @return array - result list
     */
    protected function execComposer(
        $command,
        $params = array(),
        $showInfo = false
    ) {
        // composer output, some warnings that composer/cache is not empty
        try {
            QUI::getTemp()->moveToTemp($this->vardir . 'cache');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addInfo($Exception->getMessage());
        }

        if (!isset($params['--working-dir'])) {
            $params['--working-dir'] = $this->vardir;
        }

        if ($command == 'update' || $command == 'install') {
            if ($this->getAttribute('--prefer-dist')) {
                $params['--prefer-dist'] = true;
            }
        }


        $params = array_merge(array(
            'command' => $command
        ), $params);

        $Input  = new ArrayInput($params);
        $Output = new QUI\Package\Output();

        // output events
        $PackageManager = $this;

        $Output->Events->addEvent(
            'onOutput',
            function ($message) use ($PackageManager) {
                $PackageManager->Events->fireEvent('output', array($message));
            }
        );

        QUI\System\Log::addDebug(print_r($params, true));

        // run application
        $this->getApplication()->run($Input, $Output);
        QUI\Cache\Manager::clear(self::CACHE_NAME_TYPES);

        $messages = $Output->getMessages();
        $result   = array();

        foreach ($messages as $entry) {
            if (empty($entry)) {
                continue;
            }

            if (strpos($entry, '<error>') !== false) {
                preg_match("#<error>(.*?)</error>#si", $entry, $match);

                QUI::getMessagesHandler()->addError($match[0]);
                continue;
            }

            if ($showInfo === false && strpos($entry, '<info>') !== false) {
                continue;
            }

            $result[] = $entry;
        }

        QUI\System\Log::addDebug(print_r($result, true));

        return $result;
    }


    /**
     * XML helper
     */

    /**
     * Return all packages which includes a site.xml
     *
     * @return array
     */
    public function getPackageSiteXmlList()
    {
        try {
            return QUI\Cache\Manager::get('qui/packages/list/haveSiteXml');
        } catch (QUI\Exception $Exception) {
        }

        $packages = $this->getInstalled();
        $result   = array();

        foreach ($packages as $package) {
            if (!is_dir(OPT_DIR . $package['name'])) {
                continue;
            }

            $file = OPT_DIR . $package['name'] . '/site.xml';

            if (!file_exists($file)) {
                continue;
            }

            $result[] = $package['name'];
        }

        QUI\Cache\Manager::set('qui/packages/list/haveSiteXml', $result);

        return $result;
    }

    /**
     * Return all packages which includes a site.xml
     *
     * @return array
     */
    public function getPackageDatabaseXmlList()
    {
        try {
            return QUI\Cache\Manager::get('qui/packages/list/haveDatabaseXml');
        } catch (QUI\Exception $Exception) {
        }

        $packages = $this->getInstalled();
        $result   = array();

        foreach ($packages as $package) {
            $file = OPT_DIR . $package['name'] . '/database.xml';

            if (!file_exists($file)) {
                continue;
            }

            $result[] = $package['name'];
        }

        QUI\Cache\Manager::set('qui/packages/list/haveDatabaseXml', $result);

        return $result;
    }

    /**
     * @return mixed|string
     */
    protected function getUploadPackageDir()
    {
        $updatePath = QUI::conf('update', 'updatePath');

        if (!empty($updatePath) && is_dir($updatePath)) {
            return rtrim($updatePath, '/') . '/';
        }

        return QUI::getTemp()->createFolder('quiqqerUpdate');
    }

    /**
     * Upload a archiv file to the local quiqqer repository
     *
     * @param string $file - Path to the package archive file
     *
     * @throws QUI\Exception
     */
    public function uploadPackage($file)
    {
        $dir = $this->getUploadPackageDir();

        if (!is_dir($dir)) {
            throw new QUI\Exception('Local Repository not exist');
        }

        if (!file_exists($file)) {
            throw new QUI\Exception('Archiv file not found');
        }

        $fileInfos = QUIFile::getInfo($file, array(
            'filesize'  => true,
            'mime_type' => true,
            'pathinfo'  => true
        ));

        $tempFile = $dir . '/' . $fileInfos['basename'];

        if (file_exists($tempFile)) {
            unlink($tempFile);
        }

        QUIFile::move($file, $tempFile);
    }

    /**
     * Read the locale repository and search installable packages
     *
     * @return array
     */
    public function readLocalRepository()
    {
        $dir = $this->getUploadPackageDir();

        if (!is_dir($dir)) {
            return array();
        }

        $files  = File::readDir($dir);
        $result = array();

        chdir($dir);

        foreach ($files as $package) {
            try {
                $composerJson = file_get_contents(
                    "zip://{$package}#composer.json"
                );
            } catch (\Exception $Exception) {
                // maybe gitlab package?
                try {
                    $packageName  = pathinfo($package);
                    $composerJson = file_get_contents(
                        "zip://{$package}#{$packageName['filename']}/composer.json"
                    );
                } catch (\Exception $Exception) {
                    QUI\System\Log::addDebug($Exception->getMessage());
                    continue;
                }
            }

            if (empty($composerJson)) {
                continue;
            }

            $composerJson = json_decode($composerJson, true);

            if (!isset($composerJson['name'])) {
                continue;
            }

            if (is_dir(OPT_DIR . $composerJson['name'])) {
                continue;
            }

            $result[] = $composerJson;
        }

        return $result;
    }
}
