<?php

/**
 * This file contains \QUI\Package\Manager
 */

namespace QUI\Package;

if (!defined('JSON_UNESCAPED_SLASHES')) {
    define('JSON_UNESCAPED_SLASHES', 64);
}

if (!defined('JSON_PRETTY_PRINT')) {
    define('JSON_PRETTY_PRINT', 128);
}

if (!defined('JSON_UNESCAPED_UNICODE')) {
    define('JSON_UNESCAPED_UNICODE', 256);
}

use Composer\Semver\VersionParser;
use DOMElement;
use DOMXPath;
use Exception;
use QUI;
use QUI\Cache\Manager as QUICacheManager;
use QUI\Projects\Project;
use QUI\Utils\System\File as QUIFile;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;
use UnexpectedValueException;

use function array_filter;
use function array_flip;
use function array_map;
use function array_merge;
use function array_reverse;
use function array_slice;
use function array_unique;
use function array_values;
use function bin2hex;
use function class_exists;
use function count;
use function curl_close;
use function curl_exec;
use function curl_init;
use function curl_setopt_array;
use function current;
use function date;
use function date_interval_create_from_date_string;
use function define;
use function defined;
use function dirname;
use function explode;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function hex2bin;
use function http_build_query;
use function is_array;
use function is_bool;
use function is_dir;
use function is_null;
use function is_object;
use function is_string;
use function json_decode;
use function json_encode;
use function ksort;
use function parse_url;
use function php_sapi_name;
use function phpversion;
use function print_r;
use function rtrim;
use function str_contains;
use function str_replace;
use function strcmp;
use function strip_tags;
use function strpos;
use function time;
use function trim;
use function usort;

use const CURLOPT_RETURNTRANSFER;
use const CURLOPT_URL;
use const CURLOPT_USERAGENT;
use const DEVELOPMENT;
use const JSON_PRETTY_PRINT;
use const OPT_DIR;
use const PHP_EOL;
use const PHP_URL_HOST;
use const VAR_DIR;

/**
 * Package Manager for the QUIQQER System
 *
 * Sorry, the package manager is little bit complicated
 * when the time is right, i think i must make it clearer
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @event   onOutput [ string $message ]
 *
 * @todo php composer.phar config github-oauth.github.com KEY
 */
class Manager extends QUI\QDOM
{
    const CACHE_NAME_TYPES = 'quiqqer/packages/types';

    /** @var int The minimum required memory_limit in megabytes of PHP */
    const REQUIRED_MEMORY = 128;

    /** @var int The minimum required memory_limit of PHP in megabytes, if the user added VCS repositories */
    const REQUIRED_MEMORY_VCS = 128;

    /** @var string The key used to store the package folder size in cache */
    const CACHE_KEY_PACKAGE_FOLDER_SIZE = "quiqqer/packages/package_folder_size";

    /** @var string The key used to store the package folder size in cache */
    const CACHE_KEY_PACKAGE_FOLDER_SIZE_TIMESTAMP = "quiqqer/packages/package_folder_size_timestamp";

    /** @var string The key used to store the packages with site xml files */
    const CACHE_SITE_XML_LIST = 'quiqqer/packages/list/haveSiteXml';

    /** @var string The key used to store the packages with site xml files */
    const CACHE_MEDIA_XML_LIST = 'quiqqer/packages/list/haveMediaXml';

    /** @var string The key used to store the packages with database xml files */
    const CACHE_DB_XML_LIST = 'quiqqer/packages/list/haveDatabaseXml';

    const EXCEPTION_CODE_PACKAGE_NOT_LICENSED = 1599;
    /**
     * internal event manager
     *
     * @var QUI\Events\Manager
     */
    public QUI\Events\Manager $Events;
    /**
     * internal event manager
     *
     * @var QUI\Composer\Composer|null
     */
    public ?QUI\Composer\Composer $Composer;
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
    protected string $varDir;
    /**
     * Path to the composer.json file
     *
     * @var string
     */
    protected string $composer_json;
    /**
     * Path to the composer.lock file
     *
     * @var string
     */
    protected string $composer_lock;
    /**
     * exec command to the composer.phar file
     *
     * @var string
     */
    protected string $composer_exec;
    /**
     * Packaglist - installed packages
     *
     * @var array
     */
    protected array $list = [];
    /**
     * Can composer execute via bash? shell?
     *
     * @var boolean
     */
    protected bool $exec = false;
    /**
     * temporary require packages
     *
     * @var array
     */
    protected array $require = [];
    /**
     * QUIQQER Version ->getVersion()
     *
     * @var string|null
     */
    protected ?string $version = null;
    /**
     * QUIQQER Version ->getHash()
     *
     * @var string|null
     */
    protected ?string $hash = null;
    /**
     * List of packages objects
     *
     * @var array
     */
    protected array $packages = [];
    /**
     * List of installed packages flags
     *
     * @var array
     */
    protected array $installed = [];
    /**
     * active servers - use as temp for local repo using
     *
     * @var array
     */
    protected array $activeServers = [];

    /**
     * constructor
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        // defaults
        $this->setAttributes([
            '--prefer-dist' => true
        ]);

        $this->dir = OPT_DIR; // CMS_DIR .'packages/';
        $this->varDir = VAR_DIR . 'composer/';

        $this->composer_json = $this->varDir . 'composer.json';
        $this->composer_lock = $this->varDir . 'composer.lock';

        $this->Composer = null;
        $this->Events = new QUI\Events\Manager();
        $this->setAttributes($attributes);
    }

    /**
     * Return the available QUIQQER package types
     *
     * @return array
     */
    public static function getPackageTypes(): array
    {
        return [
            'quiqqer-library', // deprecated
            'quiqqer-plugin',
            'quiqqer-module',
            'quiqqer-template',
            'quiqqer-application',
            'quiqqer-assets'
        ];
    }

    /**
     * Return the last update date
     *
     * @return integer
     *
     * @throws QUI\Exception
     */
    public function getLastUpdateCheckDate(): int
    {
        $lastCheck = (int)$this->getUpdateConf()->get('quiqqer', 'lastUpdateCheck');
        $lastUpdate = $this->getLastUpdateDate();

        if ($lastUpdate > $lastCheck) {
            $lastCheck = $lastUpdate;
        }

        return $lastCheck;
    }

    /**
     * Returns the update config object
     *
     * @return QUI\Config
     *
     * @throws QUI\Exception
     */
    protected function getUpdateConf(): QUI\Config
    {
        // set last update
        if (!file_exists(CMS_DIR . 'etc/last_update.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/last_update.ini.php', '');
        }

        return new QUI\Config(CMS_DIR . 'etc/last_update.ini.php');
    }

    /**
     * Return the last update date
     *
     * @return integer
     */
    public function getLastUpdateDate(): int
    {
        try {
            return (int)$this->getUpdateConf()->get('quiqqer', 'lastUpdate');
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError($Exception->getMessage());
            return 0;
        }
    }

    /**
     * Return the version from the composer json
     *
     * @return string
     */
    public function getVersion(): string
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

        $package = current($package);
        $this->version = $package['version'];

        return $this->version;
    }

    /**
     * @return string
     */
    public function getHash(): string
    {
        if ($this->hash) {
            return $this->hash;
        }

        if (!file_exists($this->composer_json)) {
            return '';
        }

        $this->hash = '';

        $data = file_get_contents($this->composer_lock);
        $data = json_decode($data, true);

        $package = array_filter($data['packages'], function ($package) {
            return $package['name'] === 'quiqqer/quiqqer';
        });

        $package = current($package);

        if (!empty($package['source']['reference'])) {
            $this->hash = $package['source']['reference'];
        }

        return $this->hash;
    }

    /**
     * Return the lock data from the package
     *
     * @param Package $Package
     *
     * @return array
     */
    public function getPackageLock(Package $Package): array
    {
        $data = file_get_contents($this->composer_lock);
        $data = json_decode($data, true);

        $packageName = $Package->getName();

        $package = array_filter($data['packages'], function ($package) use ($packageName) {
            return $package['name'] === $packageName;
        });

        if (empty($package)) {
            return [];
        }

        return current($package);
    }

    /**
     * Set a quiqqer version to the composer file
     * This method does not perform an update
     *
     * @param $version
     *
     * @throws UnexpectedValueException
     * @throws Exception
     */
    public function setQuiqqerVersion($version)
    {
        $Parser = new VersionParser();
        $Parser->normalize(str_replace('*', '0', $version)); // workaround, normalize cant check 1.*

        $this->version = $version;
        $this->createComposerJSON();
    }

    /**
     * Create the composer.json file for the system
     *
     * @param array $packages - add packages to the composer json
     * @throws Exception
     */
    protected function createComposerJSON(array $packages = [])
    {
        $Parser = new JsonParser();

        if (file_exists($this->composer_json)) {
            try {
                $composerJson = $Parser->parse(
                    file_get_contents($this->composer_json)
                );
            } catch (ParsingException $e) {
                throw new ParsingException(
                    'Parsing Error at file ' . $this->composer_json . PHP_EOL . $e->getMessage(),
                    $e->getDetails()
                );
            }
        } else {
            $template = file_get_contents(
                dirname(__FILE__) . '/composer.tpl'
            );

            $composerJson = $Parser->parse($template);
        }

        // config
        if (!$composerJson) {
            $composerJson = json_decode('{}');
        }

        if (!isset($composerJson->config)) {
            $composerJson->config = json_decode('{}');
        }

        $composerJson->config->{"vendor-dir"} = OPT_DIR;
        $composerJson->config->{"cache-dir"} = $this->varDir;
        $composerJson->config->{"component-dir"} = OPT_DIR . 'bin';
        $composerJson->config->{"quiqqer-dir"} = CMS_DIR;
        $composerJson->config->{"secure-http"} = true;
        $composerJson->config->{"preferred-install"} = 'dist';

        $allowedPlugins = [
            "composer/installers",
            "oomphinc/composer-installers-extender",
            "kylekatarnls/update-helper"
        ];

        if (!isset($composerJson->config->{'discard-changes'})) {
            $composerJson->config->{'discard-changes'} = true;
        }

        if (!isset($composerJson->config->{'sort-packages'})) {
            $composerJson->config->{'sort-packages'} = true;
        }

        if (!isset($composerJson->config->{'allow-plugins'})) {
            $composerJson->config->{'allow-plugins'} = json_decode('{}');
        }

        foreach ($allowedPlugins as $plugin) {
            $composerJson->config->{'allow-plugins'}->{$plugin} = true;
        }

        if (DEVELOPMENT) {
            $composerJson->{'minimum-stability'} = 'dev';
            $composerJson->config->{'preferred-install'} = 'source';
            $composerJson->{'prefer-stable'} = false;
        }

        if (!isset($composerJson->{'minimum-stability'})) {
            $composerJson->{'minimum-stability'} = 'stable';
        }

        if (isset($composerJson->{'prefer-stable'}) && $composerJson->{'prefer-stable'} === false) {
            $composerJson->{'minimum-stability'} = 'dev';
        }

        if (!isset($composerJson->{'prefer-stable'})) {
            $composerJson->{'prefer-stable'} = true;
        }

        $composerJson->extra = [
            "asset-installer-paths" => [
                "npm-asset-library" => OPT_DIR . 'bin',
                "bower-asset-library" => OPT_DIR . 'bin'
            ],
            "asset-registry-options" => [
                "npm" => false,
                "bower" => false,
                "npm-searchable" => false,
                "bower-searchable" => false
            ],
            "installer-types" => ["component"],
            "installer-paths" => [
                OPT_DIR . 'bin/{$name}/' => [
                    "type:component"
                ]
            ]
        ];

        // composer events scripts
        $composerEvents = [
            // command events
            'pre-update-cmd' => [
                'QUI\\Package\\Composer\\CommandEvents::preUpdate'
            ],
            'post-update-cmd' => [
                'QUI\\Package\\Composer\\CommandEvents::postUpdate'
            ],
            'pre-command-run' => [
                'QUI\\Package\\Composer\\CommandEvents::preCommandRun'
            ],
            // package events
            'pre-package-install' => [
                'QUI\\Package\\Composer\\PackageEvents::prePackageInstall'
            ],
            'post-package-install' => [
                'QUI\\Package\\Composer\\PackageEvents::postPackageInstall'
            ],
            'pre-package-update' => [
                'QUI\\Package\\Composer\\PackageEvents::prePackageUpdate'
            ],
            'post-package-update' => [
                'QUI\\Package\\Composer\\PackageEvents::postPackageUpdate'
            ],
            'pre-package-uninstall' => [
                'QUI\\Package\\Composer\\PackageEvents::prePackageUninstall'
            ],
            'post-package-uninstall' => [
                'QUI\\Package\\Composer\\PackageEvents::postPackageUninstall'
            ]
        ];

        if (empty($composerJson->scripts)) {
            $composerJson->scripts = (object)[];
        }

        foreach ($composerEvents as $composerEvent => $events) {
            if (empty($composerJson->scripts->{$composerEvent})) {
                $composerJson->scripts->{$composerEvent} = [];
            }

            if (!is_array($composerJson->scripts->{$composerEvent})) {
                $composerJson->scripts->{$composerEvent} = [];
            }

            $eventList = array_unique(
                array_merge(
                    $events,
                    $composerJson->scripts->{$composerEvent}
                )
            );

            $composerJson->scripts->{$composerEvent} = array_values($eventList);
        }

        // make the repository list
        $servers = $this->getServerList();
        $repositories = [];
        $npmServer = [];

        foreach ($servers as $server => $params) {
            if ($server == 'packagist.org') {
                continue;
            }

            if ($server == 'bower') {
                continue;
            }

            if ($server == 'npm') {
                continue;
            }

            if (!isset($params['active']) || $params['active'] != 1) {
                continue;
            }

            if ($params['type'] === 'npm') {
                $npmHostName = parse_url($server, PHP_URL_HOST);
                $npmServer[$npmHostName] = $server;
                continue;
            }

            if ($params['type'] === 'package') {
                if (!file_exists($server)) {
                    continue;
                }

                $repositories[] = [
                    'type' => $params['type'],
                    'package' => [
                        "name" => $params['name'],
                        "version" => $params['version'],
                        "dist" => [
                            "url" => $server,
                            "type" => "zip"
                        ]
                    ]
                ];
                continue;
            }

            $repositories[] = [
                'type' => $params['type'],
                'url' => $server
            ];
        }

        if (isset($servers['packagist.org']) && $servers['packagist.org']['active'] == 0) {
            $repositories[] = [
                'packagist.org' => false
            ];
        }

        // repositories - quiqqer/quiqqer#1260
        usort($repositories, function ($repoA, $repoB) {
            if (isset($repoA['packagist.org'])) {
                return 1;
            }

            if (isset($repoB['packagist.org'])) {
                return -1;
            }

            if (!isset($repoA['type'])) {
                return 1;
            }

            if (!isset($repoB['type'])) {
                return -1;
            }

            if ($repoA['type'] === 'vcs' && $repoB['type'] === 'vcs') {
                return 0;
            }

            if ($repoA['type'] === 'vcs' && $repoB['type'] !== 'vcs') {
                return -1;
            }

            return 1;
        });

        // license information
        $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';

        if (file_exists($licenseConfigFile)) {
            try {
                $LicenseConfig = new QUI\Config($licenseConfigFile);
                $data = $LicenseConfig->getSection('license');
                $licenseServerUrl = QUI::conf('license', 'url');

                if (
                    !empty($data['id'])
                    && !empty($data['licenseHash'])
                    && !empty($licenseServerUrl)
                ) {
                    $hash = bin2hex(QUI\Security\Encryption::decrypt(hex2bin($data['licenseHash'])));

                    $repositories[] = [
                        'type' => 'composer',
                        'url' => $licenseServerUrl,
                        'options' => [
                            'http' => [
                                'header' => [
                                    'licenseid: ' . $data['id'],
                                    'licensehash: ' . $hash,
                                    'systemid: ' . QUI\System\License::getSystemId(),
                                    'systemhash: ' . QUI\System\License::getSystemDataHash(),
                                    'clientdata: ' . bin2hex(json_encode($this->getLicenseClientData()))
                                ]
                            ]
                        ]
                    ];
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        if (isset($servers['npm']) && $servers['npm']['active'] == 1) {
            $composerJson->extra["asset-registry-options"]["npm"] = true;
            $composerJson->extra["asset-registry-options"]["npm-searchable"] = true;
        }

        if (isset($servers['bower']) && $servers['bower']['active'] == 1) {
            $composerJson->extra["asset-registry-options"]["bower"] = true;
            $composerJson->extra["asset-registry-options"]["bower-searchable"] = true;
        }

        $composerJson->repositories = $repositories;

        // add npm server
        if (!empty($npmServer)) {
            $composerJson->extra['asset-custom-npm-registries'] = $npmServer;
        }

        // standard require
        if (empty($composerJson->require)) {
            $list = $this->getList();

            // must have
            $require = [];
            $require["php"] = ">=7.2";
            $require["quiqqer/quiqqer"] = "dev-master";

            foreach ($list as $package) {
                $require[$package['name']] = $package['version'];
            }

            ksort($require);

            $composerJson->require = $require;
        }

        if (!empty($packages)) {
            foreach ($packages as $package => $version) {
                try {
                    $this->getInstalledPackage($package);

                    $Parser = new VersionParser();
                    $Parser->normalize(str_replace('*', '0', $version)); // workaround, normalize cant check 1.*

                    $composerJson->require[$package] = $version;
                } catch (Exception $Exception) {
                    QUI\System\Log::addError($Exception->getMessage());
                }
            }
        }

        if (QUI::conf('globals', 'quiqqer_version')) {
            if (is_array($composerJson->require)) {
                $composerJson->require["quiqqer/quiqqer"] = QUI::conf('globals', 'quiqqer_version');
            } elseif (is_object($composerJson->require)) {
                $composerJson->require->{"quiqqer/quiqqer"} = QUI::conf('globals', 'quiqqer_version');
            } else {
                $composerJson->require = [
                    "quiqqer/quiqqer" => QUI::conf('globals', 'quiqqer_version')
                ];
            }
        }

        // remove unneeded stuff
        if (is_array($composerJson->require)) {
            if (isset($composerJson->require['hirak/prestissimo'])) {
                unset($composerJson->require['hirak/prestissimo']);
            }

            if (isset($composerJson->require['pcsg/composer-assets'])) {
                unset($composerJson->require['pcsg/composer-assets']);
            }

            if (isset($composerJson->require['symfony/console']) && $composerJson->require['symfony/console'] === "4.*|5.*") {
                unset($composerJson->require['symfony/console']);
            }
        } elseif (is_object($composerJson->require)) {
            if (isset($composerJson->require->{'hirak/prestissimo'})) {
                unset($composerJson->require->{'hirak/prestissimo'});
            }

            if (isset($composerJson->require->{'pcsg/composer-assets'})) {
                unset($composerJson->require->{'pcsg/composer-assets'});
            }

            if (isset($composerJson->require->{'symfony/console'}) && $composerJson->require->{'symfony/console'} === "4.*|5.*") {
                unset($composerJson->require->{'symfony/console'});
            }
        }

        // save
        file_put_contents(
            $this->composer_json,
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );
    }

    /**
     * Return the server list
     *
     * @return array
     */
    public function getServerList(): array
    {
        try {
            $Config = QUI::getConfig('etc/source.list.ini.php');
            $servers = $Config->toArray();

            // replace old packagist entry with new one
            if (isset($servers['packagist'])) {
                $Config->setValue('packagist.org', 'active', $servers['packagist']['active']);
                $Config->del('packagist');
                $Config->save();

                $Config = QUI::getConfig('etc/source.list.ini.php');
                $servers = $Config->toArray();
            }

            if (!isset($servers['npm'])) {
                $servers['npm']['active'] = false;
            }

            if (!isset($servers['bower'])) {
                $servers['bower']['active'] = false;
            }

            // default types
            $servers['packagist.org']['type'] = 'composer';
            $servers['bower']['type'] = 'bower';
            $servers['npm']['type'] = 'npm';

            return $servers;
        } catch (QUI\Exception $Exception) {
        }

        return [];
    }

    /**
     * Get extra client data for composer license server header
     *
     * @return array
     */
    protected function getLicenseClientData(): array
    {
        return [
            'phpVersion' => phpversion(),
            'quiqqerHost' => QUI::conf('globals', 'host'),
            'quiqqerCmsDir' => QUI::conf('globals', 'cms_dir'),
            'quiqqerVersion' => QUI::version()
        ];
    }

    /**
     * internal get list method
     * return all installed packages and create the internal package list cache
     *
     * @return array
     */
    protected function getList(): array
    {
        if (!empty($this->list)) {
            return $this->list;
        }

        try {
            $list = QUI\Cache\LongTermCache::get(self::CACHE_NAME_TYPES);

            if (is_array($list)) {
                $this->list = $list;
                return $this->list;
            }
        } catch (QUI\Exception $Exception) {
        }

        $installed_file = $this->dir . 'composer/installed.json';

        if (!file_exists($installed_file)) {
            return [];
        }

        $data = file_get_contents($installed_file);
        $list = json_decode($data, true);

        if (isset($list['packages'])) {
            $list = $list['packages'];
        }

        $result = [];

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

        try {
            QUI\Cache\LongTermCache::set(self::CACHE_NAME_TYPES, $this->list);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $this->list;
    }

    /**
     * Return a package object
     *
     * @param string $package - name of the package
     *
     * @return QUI\Package\Package
     * @throws QUI\Exception
     */
    public function getInstalledPackage(string $package): Package
    {
        if (!isset($this->packages[$package])) {
            $this->packages[$package] = new QUI\Package\Package($package);
        }

        return $this->packages[$package];
    }

    /**
     * Set the version to packages or a package
     * This method does not perform an update
     *
     * @param array|string $packages - list of packages or package name
     * @param string $version - wanted version
     *
     * @throws UnexpectedValueException|Exception
     */
    public function setPackageVersion($packages, string $version)
    {
        if (!is_array($packages)) {
            $packages = [$packages];
        }

        $Parser = new VersionParser();
        $Parser->normalize(str_replace('*', '0', $version)); // workaround, normalize cant check 1.*

        foreach ($packages as $package) {
            try {
                $this->getInstalledPackage($package);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeDebugException($Exception);
            }
        }

        $this->createComposerJSON($packages);
    }

    /**
     * Clear the complete composer cache
     *
     * @throws QUI\Exception
     */
    public function clearComposerCache()
    {
        QUI::getTemp()->moveToTemp($this->varDir . 'repo/');
        QUI::getTemp()->moveToTemp($this->varDir . 'files/');

        $this->getComposer()->clearCache();
    }

    /**
     * Package Methods
     */

    /**
     * Return the internal composer object
     *
     * @return null|QUI\Composer\Composer
     */
    public function getComposer(): QUI\Composer\Composer
    {
        if (is_null($this->Composer)) {
            $this->Composer = new QUI\Composer\Composer($this->varDir);

            // we want to use everytime the current composer libs
            $this->Composer->setMode(
                QUI\Composer\Composer::MODE_WEB
            );
            /*
            if (php_sapi_name() != 'cli') {
                $this->Composer->setMode(QUI\Composer\Composer::MODE_WEB);
            } else {
                $this->Composer->setMode(QUI\Composer\Composer::MODE_CLI);
            }
            */
        }

        return $this->Composer;
    }

    /**
     * Returns how many packages are installed.
     *
     * This is better than counting the result of getInstalled(), since this doesn't instantiates all packages as objects.
     *
     * @return int
     */
    public function countInstalledPackages(): int
    {
        return count($this->getList());
    }

    /**
     * Return the installed packages, but filtered
     *
     * @param array $params
     * @return array
     */
    public function searchInstalledPackages(array $params = []): array
    {
        $list = $this->getList();
        $result = [];

        if (isset($params['type'])) {
            foreach ($list as $package) {
                if (!isset($package['type'])) {
                    continue;
                }

                if (!empty($params['type']) && $params['type'] != $package['type']) {
                    continue;
                }

                $result[] = $package;
            }
        }

        if (isset($params['limit']) && isset($params['page'])) {
            $limit = (int)$params['limit'];
            $page = (int)$params['page'];

            $result = QUI\Utils\Grid::getResult($result, $page, $limit);
        }

        return $result;
    }

    /**
     * Return all packages with the current versions
     *
     * @return array
     */
    public function getInstalledVersions(): array
    {
        $result = [];
        $packages = $this->getInstalled();

        foreach ($packages as $package) {
            $result[$package['name']] = $package['version'];
        }

        return $result;
    }

    /**
     * Return the installed packages
     *
     * @return array
     */
    public function getInstalled()
    {
        $list = $this->getList();
        $result = $list;

        foreach ($result as $key => $package) {
            try {
                $Package = $this->getInstalledPackage($package['name']);

                $result[$key]['title'] = $Package->getTitle();
                $result[$key]['description'] = $Package->getDescription();
                $result[$key]['image'] = $Package->getImage();
            } catch (QUI\Exception $Exception) {
            }
        }

        return $result;
    }

    /**
     * Returns the size of package folder in bytes.
     * By default, the value is returned from cache.
     * If there is no value in cache, null is returned, unless you set the force parameter to true.
     * Only if you really need to get a freshly calculated result, you may set the force parameter to true.
     * When using the force parameter expect timeouts since the calculation could take a lot of time.
     *
     * @param boolean $force - Force a calculation of the package folder size. Values aren't returned from cache. Expect timeouts.
     *
     * @return int
     */
    public function getPackageFolderSize(bool $force = false): ?int
    {
        if ($force) {
            return self::calculatePackageFolderSize();
        }

        try {
            return QUI\Cache\LongTermCache::get(self::CACHE_KEY_PACKAGE_FOLDER_SIZE);
        } catch (QUI\Cache\Exception $Exception) {
            return null;
        }
    }

    /**
     * Calculates and returns the size of the package folder in bytes.
     * The result is also stored in cache by default. Set the doNotCache parameter to true to prevent this.
     *
     * This process may take a lot of time -> Expect timeouts!
     *
     * @param boolean $doNotCache - Don't store the result in cache. Off by default.
     *
     * @return int
     */
    protected function calculatePackageFolderSize(bool $doNotCache = false): int
    {
        $packageFolderSize = QUI\Utils\System\Folder::getFolderSize($this->dir, true);

        if ($doNotCache) {
            return $packageFolderSize;
        }

        try {
            QUI\Cache\LongTermCache::set(self::CACHE_KEY_PACKAGE_FOLDER_SIZE, $packageFolderSize);
            QUI\Cache\LongTermCache::set(self::CACHE_KEY_PACKAGE_FOLDER_SIZE_TIMESTAMP, time());
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $packageFolderSize;
    }

    /**
     * Returns the timestamp when the package folder size was stored in cache.
     * Returns null if there is no data in the cache.
     *
     * @return int|null
     */
    public function getPackageFolderSizeTimestamp(): ?int
    {
        try {
            $timestamp = QUI\Cache\LongTermCache::get(self::CACHE_KEY_PACKAGE_FOLDER_SIZE_TIMESTAMP);
        } catch (QUI\Cache\Exception $Exception) {
            $timestamp = null;
        }

        return $timestamp;
    }

    /**
     * Install Package
     *
     * @param string|array $packages - name of the package, or list of paackages
     * @param string|boolean $version - (optional) version of the package default = dev-master
     *
     * @throws QUI\Exception
     */
    public function install($packages, $version = false)
    {
        QUI\System\Log::addDebug(
            'Install package ' . print_r($packages, true) . ' -> install'
        );

        QUI\Cache\Manager::clearCompleteQuiqqerCache();

        $this->composerRequireOrInstall($packages, $version);
    }

    /**
     * This will check if the Lockserver is enabled and available.
     * The package will be required or added to the lockfile and installed.
     *
     * @param $packages
     * @param $version
     *
     * @return array
     *
     * @throws PackageInstallException
     */
    protected function composerRequireOrInstall($packages, $version): array
    {
        $this->checkComposerInstallRequirements();

        // Lockserver can not handle VCS repositories ==> Check if local execution is possible or fail the operation
        if ($this->isVCSServerEnabled()) {
            return $this->getComposer()->requirePackage($packages, $version);
        }

        //
        // NO VCS enabled -> continue normal routine
        //
        if ($this->getComposer()->getMode() != QUI\Composer\Composer::MODE_WEB) {
            return $this->getComposer()->requirePackage($packages, $version);
        }

        if ($packages) {
            return $this->getComposer()->requirePackage($packages, $version);
        }

        return $this->getComposer()->install();
    }

    /**
     * Check if package install requirements are met
     *
     * @return void
     * @throws PackageInstallException
     */
    protected function checkComposerInstallRequirements()
    {
        $memoryLimit = QUI\Utils\System::getMemoryLimit();

        // Lockserver can not handle VCS repositories ==> Check if local execution is possible or fail the operation
        if ($this->isVCSServerEnabled()) {
            if ($memoryLimit >= self::REQUIRED_MEMORY_VCS * 1024 * 1024 || $memoryLimit === -1) {
                return;
            }

            throw new PackageInstallException([
                'quiqqer/quiqqer',
                'message.online.update.RAM.insufficient.vcs'
            ]);
        }

        //
        // NO VCS enabled -> continue normal routine
        //
        if ($this->getComposer()->getMode() != QUI\Composer\Composer::MODE_WEB) {
            return;
        }

        if ($memoryLimit != -1 && $memoryLimit < self::REQUIRED_MEMORY * 1024 * 1024) {
            throw new PackageInstallException([
                'quiqqer/quiqqer',
                'message.online.update.RAM.insufficient'
            ]);
        }
    }

    /**
     * Checks if a VCS update server is configured and active.
     * Returns true if at least one VCS server is active and configured. Returns false otherwise.
     *
     * @return bool
     */
    protected function isVCSServerEnabled(): bool
    {
        $servers = $this->getServerList();

        foreach ($servers as $server) {
            if ($server['type'] === 'vcs' && $server['active']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns whether the package is installed or not
     *
     * Please use this method to check the installation status and not ->getInstalledPackage()
     * This method use an internal caching
     *
     * @param string $packageName
     *
     * @return bool
     */
    public function isInstalled(string $packageName): bool
    {
        if (isset($this->installed[$packageName])) {
            return $this->installed[$packageName];
        }

        try {
            $this->getInstalledPackage($packageName);

            $this->installed[$packageName] = true;
        } catch (QUI\Exception $Exception) {
            $this->installed[$packageName] = false;
        }

        return $this->installed[$packageName];
    }

    /**
     * Install only a local package
     *
     * @param string|array $packages - name of the package
     * @param boolean $version - (optional) version of the package
     *
     * @throws QUI\Exception
     */
    public function installLocalPackage($packages, bool $version = false)
    {
        QUI\System\Log::addDebug(
            'Install package ' . print_r($packages, true) . ' -> installLocalPackage'
        );

        $this->useOnlyLocalRepository();
        $this->getComposer()->requirePackage($packages, $version);
        $this->resetRepositories();

        $this->setup($packages);
    }

    /**
     * use only the local repository
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    protected function useOnlyLocalRepository()
    {
        // deactivate active servers
        $activeServers = [];
        $serverList = $this->getServerList();

        foreach ($serverList as $server => $data) {
            if ($data['active'] == 1) {
                $activeServers[] = $server;
            }
        }

        foreach ($activeServers as $server) {
            $this->setServerStatus($server, 0, false);
        }

        // activate local repos
        LocalServer::getInstance()->activate();

        $this->createComposerJSON();
        $this->activeServers = $activeServers;
    }

    /**
     * Activate or Deactivate a server
     *
     * @param string $server - Server, IP, Host
     * @param boolean $status - 1 = active, 0 = disabled
     * @param boolean $backup - Optional (default=true, create a backup, false = create no backup
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function setServerStatus(
        string $server,
        bool $status,
        bool $backup = true
    ) {
        $Config = QUI::getConfig('etc/source.list.ini.php');
        $status = $status ? 1 : 0;

        $Config->setValue($server, 'active', $status);
        $Config->save();

        if ($backup) {
            $this->createComposerBackup();
        }

        $this->createComposerJSON();
    }

    /**
     * Creates a backup from the composer.json file
     *
     * @throws QUI\Exception
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
                $composerJson = "{$backupDir}composer_{$date}_($count).json";
                $composerLock = "{$backupDir}composer_{$date}_($count).lock";

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
     * reset the repositories after only local repo using
     *
     * @throws QUI\Exception
     * @throws Exception
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
     * Execute a setup for a package
     *
     * @param string|array $packages
     * @param array $setupOptions - optional, setup package options
     */
    public function setup($packages, array $setupOptions = [])
    {
        QUIFile::mkdir(CMS_DIR . 'etc/plugins/');

        if (!is_array($packages)) {
            $packages = [$packages];
        }

        foreach ($packages as $package) {
            try {
                $Package = $this->getInstalledPackage($package);
                $Package->setup($setupOptions);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception, QUI\System\Log::LEVEL_WARNING);
            }
        }
    }

    /**
     * Return the params of an installed package
     * If you want the Package Object, you should use getInstalledPackage
     *
     * @param string $package
     *
     * @return array
     * @throws Exception
     */
    public function getPackage(string $package): array
    {
        $cache = 'packages/cache/info/' . $package;

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        $list = $this->getList();
        $result = [];

        foreach ($list as $pkg) {
            if (!isset($pkg['name'])) {
                continue;
            }

            if ($pkg['name'] == $package) {
                $pkg['dependencies'] = $this->getDependencies($package);
                $result = $pkg;
                break;
            }
        }

        $showData = $this->show($package);

        if (isset($showData['versions'])) {
            $result['versions'] = $showData['versions'];
        }

        if (isset($showData['require '])) {
            $result['require '] = $showData['require '];
        }

        try {
            QUI\Cache\LongTermCache::set($cache, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Update Server Methods
     */

    /**
     * Return the dependencies of a package
     *
     * @param string $package - package name
     *
     * @return array - list of dependencies
     */
    public function getDependencies(string $package): array
    {
        $list = $this->getList();
        $result = [];

        foreach ($list as $pkg) {
            if (empty($pkg['require '])) {
                continue;
            }

            if (isset($pkg['require '][$package])) {
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
     * @throws Exception
     */
    public function show(string $package): array
    {
        $cache = 'packages/cache/show/' . $package;

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addDebug($Exception->getMessage());
        }

        $this->checkComposer();

        $result = [];
        $show = $this->getComposer()->show($package);

        foreach ($show as $k => $line) {
            if (!str_contains($line, ' <info>')) {
                continue;
            }

            if (!str_contains($line, ':')) {
                continue;
            }

            $line = explode(':', $line);
            $key = trim(strip_tags($line[0]));
            $value = trim(strip_tags($line[1]));

            if ($key == 'versions') {
                $value = array_map('trim', explode(',', $value));
            }

            if ($key == 'descrip.') {
                $key = 'description';
            }

            if ($line == 'requires') {
                $_temp = $show;
                $result['require'] = array_slice($_temp, $k + 1);

                continue;
            }

            $result[$key] = $value;
        }

        try {
            QUI\Cache\LongTermCache::set($cache, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Search a string in the repositories
     * Returns only not installed packages
     *
     * @param string $search - search string
     *
     * @return array
     */
    public function searchNewPackages(string $search): array
    {
        $result = [];
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
     * Search a string in the repositories
     *
     * @param string $search - search string
     *
     * @return array
     */
    public function searchPackages(string $search): array
    {
        return $this->getComposer()->search(
            QUI\Utils\Security\Orthos::clearShell($search)
        );
    }

    /**
     * Edit server from the update-server list
     *
     * @param string $server - Server, IP, Host
     * @param array $params - Server Parameter
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function editServer(string $server, array $params = [])
    {
        if (empty($server)) {
            return;
        }

        if (!is_array($params)) {
            return;
        }

        $Config = QUI::getConfig('etc/source.list.ini.php');

        // rename server
        if (isset($params['server']) && $server != $params['server']) {
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
                case "npm":
                case "bower":
                    $Config->setValue($server, 'type', $params['type']);
                    break;
            }
        }

        $Config->save();
        $this->refreshServerList();
    }

    /**
     * Add a server to the update-server list
     *
     * @param string $server - Server, IP, Host
     * @param array $params - Server Parameter
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function addServer(string $server, array $params = [])
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
                case "artifact":
                case "npm":
                case "bower":
                    $Config->setValue($server, 'type', $params['type']);
                    $Config->setValue($server, 'active', 1);
                    break;

                case "package":
                    $Config->setValue($server, 'active', 1);
                    $Config->setValue($server, 'type', $params['type']);
                    $Config->setValue($server, 'name', $params['name']);
                    $Config->setValue($server, 'version', $params['version']);
                    break;
            }
        }

        $Config->save();

        $this->refreshServerList();
    }

    /**
     * Update methods
     */

    /**
     * Refresh the server list in the var dir
     * @throws Exception
     */
    public function refreshServerList(): void
    {
        $this->createComposerJSON();
    }

    /**
     * Remove a Server completely from the update-server list
     *
     * @param string|array $server
     *
     * @throws QUI\Exception
     * @throws Exception
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
     * Check for updates
     * @throws Exception
     */
    public function checkUpdates(): bool
    {
        $this->checkComposer();

        return $this->getComposer()->updatesAvailable(false);
    }

    /**
     * Check for updates
     *
     * @param bool $force - if force is true -> database / cache output from the last check wouldn't be checked
     *
     * @return array
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public function getOutdated(bool $force = false): array
    {
        if (!is_bool($force)) {
            $force = false;
        }

        $this->checkComposer();
        $this->setLastUpdateCheckDate();

        if ($force === false) {
            // get last database check
            $result = QUI::getDataBase()->fetch([
                'from' => QUI::getDBTableName('updateChecks'),
                'where' => [
                    'result' => [
                        'type' => 'NOT',
                        'value' => ''
                    ],
                    'date' => [
                        'type' => '>=',
                        'value' => $this->getLastUpdateDate()
                    ]
                ]
            ]);

            if (!empty($result)) {
                $result = json_decode($result[0]['result'], true);

                if (!empty($result)) {
                    usort($result, function ($a, $b) {
                        return strcmp($a["package"], $b["package"]);
                    });

                    return $result;
                }
            }

            return [];
        }

        try {
            $output = $this->getOutdatedPackages();

            usort($output, function ($a, $b) {
                return strcmp($a["package"], $b["package"]);
            });

            QUI::getDataBase()->insert(QUI::getDBTableName('updateChecks'), [
                'date' => time(),
                'result' => json_encode($output)
            ]);
        } catch (QUI\Composer\Exception $Exception) {
            QUI::getDataBase()->insert(QUI::getDBTableName('updateChecks'), [
                'date' => time(),
                'error' => json_encode($Exception->toArray())
            ]);

            throw $Exception;
        }

        if (class_exists('QUI\Cron\Update')) {
            QUI\Cron\Update::setAvailableUpdates($output);
        }

        return $output;
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
     * Gets a list of outdated packages.
     * Returns an array in the format:
     * array(
     *   'package' => "vendor/package,
     *   'version' => "dev-master def567",
     *   'oldVersion' => "dev-master abc1234"
     *  );
     *
     * @return array
     * @throws QUI\Composer\Exception
     * @throws Exception
     */
    protected function getOutdatedPackages(): array
    {
        $repositories = $this->getServerList();

        foreach ($repositories as $repo) {
            if ($repo['type'] === 'vcs') {
                return $this->getComposer()->getOutdatedPackages();
            }
        }

        if ($this->getComposer()->getMode() != QUI\Composer\Composer::MODE_WEB) {
            return $this->getComposer()->getOutdatedPackages();
        }

        return $this->getComposer()->getOutdatedPackages();
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

        try {
            $this->update($package);
            $this->resetRepositories();
        } catch (Exception $Exception) {
            $this->resetRepositories();
            LocalServer::getInstance()->activate();

            throw $Exception;
        }
    }

    /**
     * Update a package or the entire system
     *
     * @param string|boolean $package - optional, package name, if false, it updates the complete system
     * @param bool $mute -mute option for the composer output
     * @param QUI\Interfaces\System\SystemOutput|null $Output
     *
     * @throws QUI\Exception
     * @throws QUI\Lockclient\Exceptions\LockServerException
     *
     * @todo if exception uncommitted changes -> own error message
     * @todo if exception uncommitted changes -> interactive mode
     */
    public function update(
        $package = false,
        bool $mute = true,
        ?QUI\Interfaces\System\SystemOutput $Output = null
    ) {
        if (!$Output) {
            $Output = new QUI\System\VoidOutput();
        }


        QUI::getEvents()->fireEvent('updateBegin');

        $Composer = $this->getComposer();

        $needledRAM = $this->isVCSServerEnabled() ? self::REQUIRED_MEMORY_VCS . 'M' : self::REQUIRED_MEMORY . 'M';
        $limit = QUI\Utils\System::getMemoryLimit();

        if (
            php_sapi_name() != 'cli'
            && $limit != -1
            && $this->isVCSServerEnabled()
            && QUIFile::getBytes($needledRAM) > $limit
        ) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.online.update.RAM.not.enough',
                    [
                        'command' => './console update'
                    ]
                )
            );
        }

        $this->createComposerBackup();

        if ($mute === true) {
            $Composer->mute();
        }

        if (is_string($package) && empty($package)) {
            $package = false;
        }

        if (!is_string($package) && !is_bool($package)) {
            $package = false;
        }

        $this->composerUpdateOrInstall($package);

        /*
        if (!empty($output) && $Composer->getMode() === QUI\Composer\Composer::MODE_WEB) {
            foreach ($output as $line) {
                if (strpos($line, '<warning>') !== false) {
                    $Output->writeLn(strip_tags($line), 'cyan');

                    // reset color
                    if (method_exists($Output, 'resetColor')) {
                        $Output->resetColor();
                    }

                    continue;
                }

                $Output->writeLn($line);
            }
        }
        */

        if ($package) {
            $Output->writeLn('Update done ... run setup for ' . $package);
            $Package = self::getInstalledPackage($package);
            $Package->setup();
        } else {
            $Output->writeLn('Update done ... run complete setup ...');
            QUI\Setup::all($Output);
        }

        // set last update
        $Output->writeLn('Cleanup database');

        QUI::getPackageManager()->setLastUpdateDate();

        QUI::getDataBase()->table()->truncate(QUI::getDBTableName('updateChecks'));
        QUI::getEvents()->fireEvent('updateEnd');
    }

    /**
     * XML helper
     */

    /**
     * This will try to retrieve the lock file from the lockserver, if the lockserver is enabled.
     * If a Lockfile has been generated by the lockserver composer will use it and execute an install.
     * If the lockserver is disabled or not available composer will issue an usual update command.
     *
     * @param bool|string - (optional) The packagename which should get updated.
     *
     * @return array
     *
     * @throws QUI\Exception
     */
    protected function composerUpdateOrInstall($package): array
    {
        $memoryLimit = QUI\Utils\System::getMemoryLimit();

        $updateOptions = [
            '--no-autoloader' => false,
            '--optimize-autoloader' => true,
            '--no-interaction' => true
        ];

        if (!DEVELOPMENT) {
            $updateOptions['--no-dev'] = true;
            $updateOptions['--interactive'] = false;
        }

        if (!empty($package) && is_string($package)) {
            $updateOptions['packages'] = [$package];
        } elseif (!empty($package) && is_array($package)) {
            $updateOptions['packages'] = $package;
        }

        // Disable lockserver if a vcs repository is used
        // Lockserver can not handle VCS repositories ==> Check if local execution is possible or fail the operation
        if ($this->isVCSServerEnabled()) {
            if ($memoryLimit >= self::REQUIRED_MEMORY_VCS * 1024 * 1024 || $memoryLimit === -1) {
                return $this->getComposer()->update($updateOptions);
            }

            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'message.online.update.RAM.insufficient.vcs.lock'
            ]);
        }

        if ($this->getComposer()->getMode() != QUI\Composer\Composer::MODE_WEB) {
            return $this->getComposer()->update($updateOptions);
        }

        if ($memoryLimit != -1 && $memoryLimit < self::REQUIRED_MEMORY * 1024 * 1024) {
            throw new QUI\Exception([
                'quiqqer/quiqqer',
                'message.online.update.RAM.insufficient'
            ]);
        }

        return $this->getComposer()->update($updateOptions);
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
     * Return all packages which includes a site.xml
     *
     * @return array
     * @todo move to an API XML Handler
     */
    public function getPackageSiteXmlList(): array
    {
        try {
            return QUI\Cache\LongTermCache::get(self::CACHE_SITE_XML_LIST);
        } catch (QUI\Exception $Exception) {
        }

        $packages = $this->getInstalled();
        $result = [];

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

        try {
            QUI\Cache\LongTermCache::set(self::CACHE_SITE_XML_LIST, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Return all packages which includes a media.xml
     *
     * @return array
     * @todo move to an API XML Handler
     */
    public function getPackageMediaXmlList(): array
    {
        try {
            return QUI\Cache\LongTermCache::get(self::CACHE_MEDIA_XML_LIST);
        } catch (QUI\Exception $Exception) {
        }

        $packages = $this->getInstalled();
        $result = [];

        foreach ($packages as $package) {
            if (!is_dir(OPT_DIR . $package['name'])) {
                continue;
            }

            $file = OPT_DIR . $package['name'] . '/media.xml';

            if (!file_exists($file)) {
                continue;
            }

            $result[] = $package['name'];
        }

        try {
            QUI\Cache\LongTermCache::set(self::CACHE_MEDIA_XML_LIST, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Return all packages which includes a site.xml
     *
     * @return array
     * @todo move to an API XML Handler
     */
    public function getPackageDatabaseXmlList(): array
    {
        try {
            return QUI\Cache\LongTermCache::get(self::CACHE_DB_XML_LIST);
        } catch (QUI\Exception $Exception) {
        }

        $packages = $this->getInstalled();
        $result = [];

        foreach ($packages as $package) {
            $file = OPT_DIR . $package['name'] . '/database.xml';

            if (!file_exists($file)) {
                continue;
            }

            $result[] = $package['name'];
        }

        try {
            QUI\Cache\LongTermCache::set(self::CACHE_DB_XML_LIST, $result);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);
        }

        return $result;
    }

    /**
     * Get specific XML file of all packages that provide it
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     *
     * @return array - absolute file paths
     */
    public function getPackageXMLFiles(string $name): array
    {
        // @todo cache

        $packages = $this->getInstalled();
        $result = [];

        foreach ($packages as $package) {
            $file = OPT_DIR . $package['name'] . '/' . $name;

            if (!file_exists($file)) {
                continue;
            }

            $result[] = $file;
        }

        return $result;
    }

    /**
     * Returns all site types that are available
     *
     * @param Project|boolean $Project - optional
     * @return array
     */
    public function getAvailableSiteTypes($Project = false): array
    {
        $types = [];
        $installed = $this->getInstalled();

        foreach ($installed as $package) {
            $name = $package['name'];
            $siteXml = OPT_DIR . $name . '/site.xml';

            if (!file_exists($siteXml)) {
                continue;
            }

            $typeList = QUI\Utils\Text\XML::getTypesFromXml($siteXml);

            foreach ($typeList as $Type) {
                /* @var $Type DOMElement */
                $types[$name][] = [
                    'type' => $name . ':' . $Type->getAttribute('type'),
                    'icon' => $Type->getAttribute('icon'),
                    'text' => $this->getSiteTypeName(
                        $name . ':' . $Type->getAttribute('type')
                    ),
                    'childrenType' => $Type->getAttribute('child-type'),
                    'childrenNavHide' => $Type->getAttribute('child-navHide')
                ];
            }
        }

        ksort($types);

        // standard to top
        $types = array_reverse($types, true);

        $types['standard'] = [
            'type' => 'standard',
            'icon' => 'fa fa-file-o'
        ];

        return array_reverse($types, true);
    }

    /**
     * Get the full Type name
     *
     * @param string $type - site type
     * @return string
     */
    public function getSiteTypeName(string $type): string
    {
        if ($type == 'standard' || empty($type)) {
            return QUI::getLocale()->get('quiqqer/quiqqer', 'site.type.standard');
        }

        // \QUI\System\Log::write( $type );
        $data = $this->getSiteXMLDataByType($type);

        if (isset($data['locale'])) {
            return QUI::getLocale()->get(
                $data['locale']['group'],
                $data['locale']['var']
            );
        }

        if (empty($data['value'])) {
            return $type;
        }

        $value = explode(' ', $data['value']);

        if (QUI::getLocale()->exists($value[0], $value[1])) {
            return QUI::getLocale()->get($value[0], $value[1]);
        }

        return $type;
    }

    /**
     * Return the data for a type from its site.xml
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $type
     * @return boolean|array
     */
    protected function getSiteXMLDataByType(string $type)
    {
        $cache = 'quiqqer/packages/xml-data/' . $type;

        try {
            return QUI\Cache\LongTermCache::get($cache);
        } catch (QUI\Cache\Exception $Exception) {
        }

        if (strpos($type, ':') === false) {
            return false;
        }

        $explode = explode(':', $type);
        $package = $explode[0];
        $type = $explode[1];

        $siteXml = OPT_DIR . $package . '/site.xml';

        if (!file_exists($siteXml)) {
            return false;
        }

        $Dom = QUI\Utils\Text\XML::getDomFromXml($siteXml);
        $XPath = new DOMXPath($Dom);
        $Types = $XPath->query('//type[@type="' . $type . '"]');

        if (!$Types->length) {
            return false;
        }

        /* @var $Type DOMElement */
        $Type = $Types->item(0);
        $data = [];

        if ($Type->getAttribute('icon')) {
            $data['icon'] = $Type->getAttribute('icon');
        }

        if ($Type->getAttribute('extend')) {
            $data['extend'] = $Type->getAttribute('extend');
        }

        $loc = $Type->getElementsByTagName('locale');

        if ($loc->length) {
            $data['locale'] = [
                'group' => $loc->item(0)->getAttribute('group'),
                'var' => $loc->item(0)->getAttribute('var')
            ];
        }

        $data['value'] = trim($Type->nodeValue);

        QUI\Cache\LongTermCache::set($cache, $data);

        return $data;
    }

    //region site types

    /**
     * Return the type icon
     *
     * @param string $type
     * @return string
     */
    public function getIconBySiteType(string $type): string
    {
        $data = $this->getSiteXMLDataByType($type);

        return $data['icon'] ?? '';
    }

    /**
     * Checks if this QUIQQER system has the license to use a certain package.
     *
     * @param string $package - Package name (internal)
     * @return bool
     */
    public function hasLicense(string $package): bool
    {
        $cacheName = 'quiqqer_licenses/' . $package;

        try {
            return QUICacheManager::get($cacheName);
        } catch (Exception $Exception) {
            // nothing, make license server request
        }

        $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';

        if (!file_exists($licenseConfigFile)) {
            return false;
        }

        try {
            $licenseData = QUI\System\License::getLicenseData();

            if (empty($licenseData['id']) || empty($licenseData['licenseHash'])) {
                return false;
            }

            $licenseServerUrl = QUI\System\License::getLicenseServerUrl() . 'api/license/haslicensedpackage?';
            $licenseServerUrl .= http_build_query([
                'licenseid' => $licenseData['id'],
                'licensehash' => $licenseData['licenseHash'],
                'systemid' => QUI\System\License::getSystemId(),
                'systemhash' => QUI\System\License::getSystemDataHash(),
                'package' => $package
            ]);

            $Curl = curl_init();

            curl_setopt_array($Curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $licenseServerUrl,
                CURLOPT_USERAGENT => 'QUIQQER'
            ]);

            $response = curl_exec($Curl);

            curl_close($Curl);

            $isLicensed = !empty($response);

            QUICacheManager::set($cacheName, $isLicensed, date_interval_create_from_date_string('1 day'));

            return $isLicensed;
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return false;
        }
    }

    /**
     * Checks if this QUIQQER system has the license to use a certain package.
     *
     * @param string $package - Package name (internal)
     * @return bool|array
     */
    public function getPackageStoreUrls(string $package)
    {
        $cacheName = 'quiqqer_packagestore_urls/' . $package;

        try {
            return json_decode(QUI\Cache\LongTermCache::get($cacheName), true);
        } catch (Exception $Exception) {
            // nothing, make license server request
        }

        $licenseConfigFile = CMS_DIR . 'etc/license.ini.php';

        if (!file_exists($licenseConfigFile)) {
            return false;
        }

        try {
            $licenseServerUrl = QUI::conf('license', 'url');

            if (empty($licenseServerUrl)) {
                return false;
            }

            $licenseServerUrl = rtrim($licenseServerUrl) . 'api/license/getstoreurls?';

            $licenseServerUrl .= http_build_query([
                'package' => $package
            ]);

            $Curl = curl_init();

            curl_setopt_array($Curl, [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_URL => $licenseServerUrl,
                CURLOPT_USERAGENT => 'QUIQQER'
            ]);

            $response = curl_exec($Curl);

            curl_close($Curl);

            if (empty($response)) {
                return false;
            }

            $urls = json_decode($response, true);

            QUI\Cache\LongTermCache::set($cacheName, $response);

            return $urls;
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return false;
        }
    }

    /**
     * Return the composer array
     *
     * @return array
     * @throws Exception
     */
    protected function getComposerJSON(): array
    {
        $this->checkComposer();
        $json = file_get_contents($this->composer_json);

        return json_decode($json, true);
    }

    //endregion

    /**
     * Checks if the composer.json exists
     * if not, the system will try to create the composer.json (with all installed packages)
     *
     * @throws Exception
     */
    protected function checkComposer()
    {
        if (file_exists($this->composer_json)) {
            return;
        }

        $this->createComposerJSON();
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

        $this->list = [];

        if (is_array($list)) {
            $this->list = $list;
        }
    }
}
