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
     * internal event manager
     *
     * @var QUI\Events\Manager
     */
    public $Events;

    /**
     * internal event manager
     *
     * @var QUI\Composer\Composer
     */
    public $Composer;

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

        $this->Composer = new QUI\Composer\Composer($this->vardir);
        $this->Events   = new QUI\Events\Manager();
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
     * Return the last update date
     *
     * @return integer
     */
    public function getLastUpdateCheckDate()
    {
        return (int)$this->getUpdateConf()->get('quiqqer', 'lastUpdateCheck');
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
     * Set the last update date to now
     *
     * @throws QUI\Exception
     */
    public function setLastUpdateCheckDate()
    {
        $Last = $this->getUpdateConf();
        $Last->set('quiqqer', 'lastUpdateCheck', time());
        $Last->save();
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

        $data = file_get_contents($this->composer_lock);
        $data = json_decode($data, true);

        $package = array_filter($data['packages'], function ($package) {
            return $package['name'] === 'quiqqer/quiqqer';
        });

        $package       = current($package);
        $this->version = $package['version'];

        return $this->version;
    }

    /**
     * Return the lock data from the package
     *
     * @param Package $Package
     * @return array
     */
    public function getPackageLock(Package $Package)
    {
        $data = file_get_contents($this->composer_lock);
        $data = json_decode($data, true);

        $packageName = $Package->getName();

        $package = array_filter($data['packages'], function ($package) use ($packageName) {
            return $package['name'] === $packageName;
        });

        if (empty($package)) {
            return array();
        }

        $package = current($package);

        return $package;
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
            "vendor-dir"        => OPT_DIR,
            "cache-dir"         => $this->vardir,
            "component-dir"     => OPT_DIR . 'bin',
            "quiqqer-dir"       => CMS_DIR,
            "minimum-stability" => 'dev',
            "secure-http"       => false
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

        $this->Composer->clearCache();
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

            $result = QUI\Utils\Grid::getResult($result, $page, $limit);
        }

        foreach ($result as $key => $package) {
            try {
                $Package = $this->getInstalledPackage($package['name']);

                $result[$key]['title']       = $Package->getTitle();
                $result[$key]['description'] = $Package->getDescription();
                $result[$key]['image']       = $Package->getImage();
            } catch (QUI\Exception $Exception) {
            }
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
            'Install package ' . $package . ' -> install'
        );

        $this->Composer->requirePackage($package, $version);

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
            'Install package ' . $package . ' -> installLocalPackage'
        );

        $this->useOnlyLocalRepository();
        $this->Composer->requirePackage($package, $version);
        $this->resetRepositories();

        $this->setup($package);
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
     * Return package details, via composer
     * If you want a local package, please use getInstalledPackage() and use the Package instead
     *
     * @param string $package - Name of the package eq: quiqqer/quiqqer
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
        $show   = $this->Composer->show($package);

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
     * Search a string in the repositories
     *
     * @param string $search - search string
     *
     * @return array
     */
    public function searchPackages($search)
    {
        return $this->Composer->search(
            QUI\Utils\Security\Orthos::clearShell($search)
        );
    }

    /**
     * Search a string in the repositories
     * Returns only not installed packages
     *
     * @param string $search - search string
     * @return array
     */
    public function searchNewPackagess($search)
    {
        $result   = array();
        $packages = $this->searchPackages($search);

        $installed = array_map(function ($entry) {
            return $entry['name'];
        }, $this->getList());

        $installed = array_flip($installed);

        foreach ($packages as $package => $description) {
            if (!isset($installed[$package])) {
                $result[$package] = $description;
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
     * Edit server from the update-server list
     *
     * @param string $server - Server, IP, Host
     * @param array $params - Server Parameter
     */
    public function editServer($server, $params = array())
    {
        if (empty($server)) {
            return;
        }

        if (!is_array($params)) {
            return;
        }

        $Config = QUI::getConfig('etc/source.list.ini.php');

        // rename server
        if (isset($params['server'])
            && $server != $params['server']
        ) {
            $this->addServer($params['server'], $Config->getSection($server));
            $this->removeServer($server);
            $server = $params['server'];
        }

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

        return $this->Composer->updatesAvailable(false);
    }

    /**
     * Check for updates
     *
     * @param bool $force - if force is true -> database / cache output from the last check wouldn't be checked
     * @return array
     *
     * @throws \QUI\Exception
     */
    public function getOutdated($force = false)
    {
        $this->checkComposer();
        $this->setLastUpdateCheckDate();

        if ($force === false) {
            // get last database check
            $result = QUI::getDataBase()->fetch(array(
                'from'  => QUI::getDBTableName('updateChecks'),
                'where' => array(
//                    'error' => array(
//                        'type'  => 'NOT',
//                        'value' => ''
//                    ),
                    'result' => array(
                        'type'  => '>=',
                        'value' => $this->getLastUpdateDate()
                    )
                )
            ));
            QUI\System\Log::writeRecursive($result);
            if (!empty($result)) {
                return json_decode($result[0]['data'], true);
            }
        }

        try {
            $output = $this->Composer->outdated();

            QUI::getDataBase()->insert(QUI::getDBTableName('updateChecks'), array(
                'date'   => time(),
                'result' => json_encode($output)
            ));
        } catch (QUI\Composer\Exception $Exception) {
            QUI::getDataBase()->insert(QUI::getDBTableName('updateChecks'), array(
                'date'  => time(),
                'error' => json_encode($Exception->toArray())
            ));

            throw $Exception;
        }

        return $output;
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

        if (!empty($package)) {
            $output = $this->Composer->update(array(
                'packages' => array($package)
            ));
        } else {
            $output = $this->Composer->update();
        }

        QUI\System\Log::addDebug(implode("\n", $output));

        // composer optimize
        $optimize = $this->Composer->dumpAutoload(array(
            'optimize' => true
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
        $this->createComposerBackup();
        $this->useOnlyLocalRepository();

        $this->update($package);

        $this->resetRepositories();
    }

    /**
     * use only the local repository
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
     * XML helper
     */

    /**
     * Return all packages which includes a site.xml
     *
     * @return array
     * @todo move to an API XML Handler
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
     * @todo move to an API XML Handler
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

        $files  = QUIFile::readDir($dir);
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
