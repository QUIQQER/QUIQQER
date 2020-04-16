<?php

/**
 * This file contains namespace QUI\Package\Package
 */

namespace QUI\Package;

use QUI;
use QUI\Update;
use QUI\Utils\Text\XML;
use QUI\Cache\LongTermCache;

/**
 * An installed package
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event  onPackageSetup [ this ]
 * @event  packageInstallBefore [ this ]
 * @event  onPackageInstall [ this ]
 * @event  onPackageInstallAfter [ this ]
 * @event  onPackageUninstall [ string PackageName ]
 */
class Package extends QUI\QDOM
{
    const CONSOLE_XML = 'console.xml';
    const DATABASE_XML = 'database.xml';
    const EVENTS_XML = 'events.xml';
    const GROUP_XML = 'group.xml';
    const LOCALE_XML = 'locale.xml';
    const MENU_XML = 'menu.xml';
    const PANELS_XML = 'panels.xml';
    const PERMISSIONS_XML = 'permissions.xml';
    const SETTINGS_XML = 'settings.xml';
    const SITE_XML = 'site.xml';
    const USER_XML = 'user.xml';
    const WIDGETS_XML = 'widgets.xml';

    /**
     * Name of the package
     *
     * @var string
     */
    protected $name = '';

    /**
     * Title of the package
     *
     * @var null
     */
    protected $title = null;

    /**
     * Description of the package
     *
     * @var null
     */
    protected $description = null;

    /**
     * Directory of the package
     *
     * @var string
     */
    protected $packageDir = '';

    /**
     * @var null
     */
    protected $packageXML = null;

    /**
     * Package composer data from the composer file
     *
     * @var bool|array
     */
    protected $composerData = false;

    /**
     * Path to the Config
     *
     * @var string
     */
    protected $configPath = null;

    /**
     * Package Config
     *
     * @var QUI\Config
     */
    protected $Config = null;

    /**
     * @var bool
     */
    protected $isQuiqqerPackage = false;

    /**
     * @var bool
     */
    protected $readPackageInfo = false;

    /**
     * constructor
     *
     * @param string $package - Name of the Package
     *
     * @throws \QUI\Exception
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR.$package.'/';

        if (\strpos($package, '-asset/') !== false) {
            $packageDir = OPT_DIR.'/bin/'.\explode('/', $package)[1].'/';
        }

        if (!\is_dir($packageDir)) {
            $package = \htmlspecialchars($package);
            throw new QUI\Exception('Package not exists ['.$package.']', 404);
        }

        $this->packageDir = $packageDir;
        $this->name       = $package;
    }

    /**
     * read the package data
     */
    protected function readPackageData()
    {
        if ($this->readPackageInfo) {
            return;
        }

        // no composer.json, no real package
        if (!\file_exists($this->packageDir.'composer.json')) {
            $this->readPackageInfo = true;

            return;
        }

        $this->getComposerData();

        // ERROR
        if (!$this->composerData) {
            QUI\System\Log::addCritical(
                'Package composer.json has some errors: '.\json_last_error_msg(),
                [
                    'package'    => $this->name,
                    'packageDir' => $this->packageDir
                ]
            );
        }

        if (!isset($this->composerData['type'])) {
            $this->readPackageInfo = true;

            return;
        }

        if (\strpos($this->composerData['type'], 'quiqqer-') === false) {
            $this->readPackageInfo = true;

            return;
        }

        $this->isQuiqqerPackage = true;
        $this->configPath       = CMS_DIR.'etc/plugins/'.$this->getName().'.ini.php';

        QUI\Utils\System\File::mkfile($this->configPath);


        $this->readPackageInfo = true;
    }

    /**
     * Read the package xml
     *
     * @return array
     */
    protected function getPackageXMLData()
    {
        if ($this->packageXML !== null) {
            return $this->packageXML;
        }

        if (!$this->isQuiqqerPackage()) {
            $this->packageXML = [];

            return [];
        }

        $packageXML = $this->packageDir.'/package.xml';

        // package xml
        if (!\file_exists($packageXML)) {
            $this->packageXML = [];

            return $this->packageXML;
        }

        $this->packageXML = XML::getPackageFromXMLFile($packageXML);

        return $this->packageXML;
    }

    /**
     * Return the system path of the package
     *
     * @return string
     */
    public function getDir()
    {
        return $this->packageDir;
    }

    /**
     * Return the cache name for this package
     *
     * @return string
     */
    public function getCacheName()
    {
        return 'quiqqer/package/'.$this->getName();
    }

    /**
     * Alias for getCacheName()
     *
     * @return string
     */
    public function getCachePath()
    {
        return $this->getCacheName();
    }

    /**
     * Return all providers
     *
     * @param string|bool $providerName - optional, Name of the wanted providers
     * @return array
     *
     * @todo cache that
     */
    public function getProvider($providerName = false)
    {
        $packageData = $this->getPackageXMLData();

        if (empty($packageData['provider'])) {
            return [];
        }

        if ($providerName === false) {
            return $packageData['provider'];
        }

        $provider = $packageData['provider'];
        $provider = \array_filter($provider, function ($key) use ($providerName) {
            return $key === $providerName;
        }, \ARRAY_FILTER_USE_KEY);

        if (!isset($provider[$providerName])) {
            return [];
        }

        return $provider[$providerName];
    }

    /**
     * Return the template parent
     * - if one is set
     *
     * @return bool|Package
     */
    public function getTemplateParent()
    {
        $packageData = $this->getPackageXMLData();

        if (empty($packageData['template_parent'])) {
            return false;
        }

        try {
            return QUI::getPackage($packageData['template_parent']);
        } catch (QUI\Exception $Exception) {
            return false;
        }
    }

    /**
     * Has the package a template parent?
     * If the package is a template, its possible that the template has a package
     *
     * @return bool
     */
    public function hasTemplateParent()
    {
        $parent = $this->getTemplateParent();

        return !empty($parent);
    }

    /**
     * Return the var dir for the package
     * you can use the var dir for not accessible files
     *
     * @return string
     */
    public function getVarDir()
    {
        $varDir = VAR_DIR.'package/'.$this->getName().'/';

        QUI\Utils\System\File::mkdir($varDir);

        return $varDir;
    }

    /**
     * Return the system path of the package
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Return the package title
     *
     * @return string
     */
    public function getTitle()
    {
        if ($this->title) {
            return $this->title;
        }

        $packageData = $this->getPackageXMLData();

        if (isset($packageData['title']) && !empty($packageData['title'])) {
            $this->title = $packageData['title'];

            return $this->title;
        }

        if ($this->isQuiqqerPackage()
            && QUI::getLocale()->exists($this->name, 'package.title')
        ) {
            $this->title = QUI::getLocale()->get($this->name, 'package.title');

            return $this->title;
        }


        $this->title = $this->getName();

        return $this->title;
    }

    /**
     * Return the package description
     *
     * @return String
     */
    public function getDescription()
    {
        if ($this->description) {
            return $this->description;
        }

        $packageData = $this->getPackageXMLData();

        if (isset($packageData['description'])) {
            $this->description = $packageData['description'];

            return $this->description;
        }

        if ($this->isQuiqqerPackage()
            && QUI::getLocale()->exists($this->name, 'package.description')
        ) {
            $this->description = QUI::getLocale()->get($this->name, 'package.description');

            return $this->description;
        }

        $composer = $this->getComposerData();

        if (isset($composer['description'])) {
            $this->description = $composer['description'];

            return $this->description;
        }


        $this->description = '';

        return $this->description;
    }

    /**
     * Return the path to the package image / icon
     *
     * @return String
     */
    public function getImage()
    {
        $packageData = $this->getPackageXMLData();

        if (isset($packageData['image'])) {
            return $packageData['image'];
        }

        if (\file_exists($this->packageDir.'bin/package.png')) {
            return \str_replace(OPT_DIR, URL_OPT_DIR, $this->packageDir).'bin/package.png';
        }

        return '';
    }

    /**
     * Return the permission name for a package permission
     * eq:
     * - canUse
     *
     * @param string $permissionName
     * @return mixed
     */
    public function getPermissionName($permissionName = 'canUse')
    {
        $nameShortCut = \preg_replace("/[^A-Za-z0-9 ]/", '', $this->getName());

        switch ($permissionName) {
            case 'header':
                return 'permission.quiqqer.packages.'.$nameShortCut.'._header';

            default:
                return 'quiqqer.packages.'.$nameShortCut.'.canUse';
        }
    }

    /**
     * Return all preview images
     * Not the main image
     *
     * @return array
     */
    public function getPreviewImages()
    {
        $packageData = $this->getPackageXMLData();

        if (!isset($packageData['preview']) || !\is_array($packageData['preview'])) {
            return [];
        }

        return $packageData['preview'];
    }

    /**
     * Return the package config
     *
     * @return QUI\Config|boolean
     *
     * @throws QUI\Exception
     */
    public function getConfig()
    {
        if ($this->configPath === null) {
            $configFile = CMS_DIR.'etc/plugins/'.$this->getName().'.ini.php';

            if (\file_exists($configFile)) {
                $this->configPath = $configFile;
            }
        }

        if (empty($this->configPath)) {
            return false;
        }

        if (!$this->Config) {
            $this->Config = new QUI\Config($this->configPath);
        }

        return $this->Config;
    }

    /**
     * Return the package lock data
     *
     * @return array
     */
    public function getLock()
    {
        return QUI::getPackageManager()->getPackageLock($this);
    }

    /**
     * Return the composer data of the package
     *
     * @return array|bool|mixed
     */
    public function getComposerData()
    {
        if ($this->composerData) {
            return $this->composerData;
        }

        $cache = $this->getCacheName().'/composerData';

        try {
            $this->composerData = LongTermCache::get($cache);

            return $this->composerData;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }


        if (\file_exists($this->packageDir.'composer.json')) {
            $this->composerData = \json_decode(
                \file_get_contents($this->packageDir.'composer.json'),
                true
            );
        }

        if (\file_exists($this->packageDir.'package.json')) {
            $this->composerData = \json_decode(
                \file_get_contents($this->packageDir.'package.json'),
                true
            );
        }

        if (\file_exists($this->packageDir.'bower.json')) {
            $this->composerData = \json_decode(
                \file_get_contents($this->packageDir.'bower.json'),
                true
            );
        }

        $lock = QUI::getPackageManager()->getPackageLock($this);

        if (isset($lock['version'])) {
            $this->composerData['version'] = $lock['version'];
        }

        LongTermCache::set($cache, $this->composerData);

        return $this->composerData;
    }

    /**
     * Clears the package cache
     */
    public function clearCache()
    {
        LongTermCache::clear($this->getCacheName());
    }

    /**
     * Return the requirements / dependencies of the package
     *
     * @return array
     */
    public function getDependencies()
    {
        $composer = $this->getComposerData();

        if (isset($composer['require'])) {
            return $composer['require'];
        }

        return [];
    }

    /**
     * Get specific XML file from Package
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     * @return string|false - absolute file path or false if xml file does not exist
     */
    public function getXMLFilePath($name)
    {
        $file = $this->getDir().$name;

        if (!\file_exists($file)) {
            return false;
        }

        return $file;
    }

    /**
     * use getXMLFilePath()
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     * @return string|false - absolute file path or false if xml file does not exist
     * @deprecated
     */
    public function getXMLFile($name)
    {
        return $this->getXMLFilePath($name);
    }

    /**
     * Checks the package permisson
     *
     * @param string $permission - could be canUse
     * @param QUI\Interfaces\Users\User|null $User
     *
     * @return bool
     */
    public function hasPermission($permission = 'canUse', $User = null)
    {
        if (!QUI::conf('permissions', 'package')) {
            return true;
        }

        switch ($permission) {
            default:
            case 'canUse':
                return QUI\Permissions\Permission::hasPermission(
                    $this->getPermissionName($permission),
                    $User
                );
        }
    }

    /**
     * Execute the package setup
     *
     * @param array $params - optional ['localePublish' => true, 'localeImport' => true, 'forceImport' => false]
     * @throws QUI\Exception
     */
    public function setup($params = [])
    {
        $this->readPackageData();

        QUI::getEvents()->fireEvent('packageSetupBegin', [$this]);

        // options
        $optionLocalePublish = true;
        $optionLocaleImport  = true;
        $optionForceImport   = false;

        if (isset($params['localePublish'])) {
            $optionLocalePublish = $params['localePublish'];
        }

        if (isset($params['localeImport'])) {
            $optionLocaleImport = $params['localeImport'];
        }

        if (isset($params['forceImport'])) {
            $optionForceImport = $params['forceImport'];
        }


        $dir = $this->getDir();

        if (!$this->isQuiqqerPackage()) {
            QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);

            return;
        }

        // permissions
        if ($this->getName() != 'quiqqer/quiqqer') { // you can't set permissions to the core
            try {
                $found = QUI::getDataBase()->fetch([
                    'from'  => QUI\Permissions\Manager::table(),
                    'where' => [
                        'name' => $this->getPermissionName()
                    ],
                    'limit' => 1
                ]);

                if (!isset($found[0])) {
                    QUI::getPermissionManager()->addPermission([
                        'name'         => $this->getPermissionName(),
                        'title'        => 'quiqqer/quiqqer permission.package.canUse',
                        'desc'         => '',
                        'area'         => '',
                        'type'         => 'bool',
                        'defaultvalue' => 1
                    ]);
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }


            $languages = QUI\Translator::getAvailableLanguages();

            $data = [
                'datatype' => 'php,js',
                'package'  => $this->getName()
            ];

            foreach ($languages as $lang) {
                $data[$lang] = QUI::getLocale()->getByLang($lang, $this->getName(), 'package.title');
            }

            try {
                QUI\Translator::addUserVar(
                    'quiqqer/quiqqer',
                    $this->getPermissionName('header'),
                    $data
                );
            } catch (QUI\Exception $Exception) {
                try {
                    QUI\Translator::edit(
                        'quiqqer/quiqqer',
                        $this->getPermissionName('header'),
                        $this->getName(),
                        $data
                    );
                } catch (\Exception $Exception) {
                    QUI::getMessagesHandler()->addAttention(
                        $Exception->getMessage()
                    );
                }
            }
        }

        // xml
        Update::importDatabase($dir.self::DATABASE_XML);
        Update::importTemplateEngines($dir.'engines.xml');
        Update::importEditors($dir.'wysiwyg.xml');

        QUI::getPermissionManager()->deletePermissionsFromPackage($this);

        Update::importPermissions($dir.self::PERMISSIONS_XML, $this->getName());
        Update::importMenu($dir.self::MENU_XML);

        // events
        QUI\Events\Manager::clear($this->getName());
        Update::importEvents($dir.self::EVENTS_XML, $this->getName());
        Update::importSiteEvents($dir.self::SITE_XML);

        // locale
        if ($optionLocaleImport) {
            QUI\Translator::batchImportFromPackage($this);
        }

        if ($optionLocalePublish) {
            $this->setupLocalePublish();
        }


        // settings
        if (!\file_exists($dir.self::SETTINGS_XML)) {
            QUI::getEvents()->fireEvent('packageSetup', [$this]);
            QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);

            return;
        }

        $Config = XML::getConfigFromXml($dir.self::SETTINGS_XML);

        if ($Config) {
            $Config->save();
        }

        QUI::getEvents()->fireEvent('packageSetup', [$this]);
        QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);
    }

    /**
     * publish the locale files of the package
     */
    protected function setupLocalePublish()
    {
        $dir = $this->getDir();

        try {
            $groups   = [];
            $files    = [$dir.self::LOCALE_XML];
            $Dom      = XML::getDomFromXml($dir.self::LOCALE_XML);
            $FileList = $Dom->getElementsByTagName('file');

            if ($FileList->length) {
                /** @var \DOMElement $File */
                foreach ($FileList as $File) {
                    $files[] = $this->getDir().\ltrim($File->getAttribute('file'), '/');
                }
            }

            foreach ($files as $file) {
                $groups = XML::getLocaleGroupsFromDom(
                    XML::getDomFromXml($file)
                );
            }

            $groups = \array_map(function ($data) {
                return $data['group'];
            }, $groups);

            $groups = \array_unique($groups);
        } catch (\Exception $Exception) {
            $groups = [];
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        $groups[] = $this->getName();
        $groups[] = 'quiqqer/quiqqer';

        $groups = \array_unique($groups);

        foreach ($groups as $group) {
            try {
                QUI\Translator::publish($group);
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }
        }
    }

    /**
     * Is the package a quiqqer package?
     *
     * @return bool
     */
    public function isQuiqqerPackage()
    {
        $this->readPackageData();

        return $this->isQuiqqerPackage;
    }

    /**
     * Execute first install
     *
     * @throws QUI\Exception
     */
    public function install()
    {
        $this->readPackageData();

        QUI::getEvents()->fireEvent('packageInstallBefore', [$this]);

        Update::importEvents(
            $this->getDir().self::EVENTS_XML,
            $this->getName()
        );

        QUI::getEvents()->fireEvent('packageInstall', [$this]);

        if ($this->isQuiqqerPackage()) {
            $this->setup();
        }

        QUI\Cache\Manager::clearSettingsCache();
        QUI\Cache\Manager::clearCompleteQuiqqerCache();

        QUI::getEvents()->fireEvent('packageInstallAfter', [$this]);
    }

    /**
     * Uninstall the package / plugin
     * it doesn't destroy the database data, its only uninstall the package
     *
     * @throws QUI\Exception
     */
    public function uninstall()
    {
        QUI::getEvents()->fireEvent('packageUnInstall', [$this->getName()]);

        // remove events
        QUI::getEvents()->removePackageEvents($this);
    }

    /**
     * Destroy the complete package / plugin
     * it destroy the database data, too
     *
     * @throws QUI\Exception
     * @todo implementieren
     */
    public function destroy()
    {
        QUI::getPermissionManager()->removePermission($this->getPermissionName());
        QUI::getPermissionManager()->removePermission($this->getPermissionName('header'));

        QUI::getEvents()->fireEvent('packageDestroy', [$this->getName()]);
    }

    /**
     * event on update
     *
     * @throws QUI\Exception
     */
    public function onUpdate()
    {
        QUI::getEvents()->fireEvent('packageUpdate', [$this]);
    }
}
