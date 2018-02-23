<?php

/**
 * This file contains namespace QUI\Package\Package
 */

namespace QUI\Package;

use QUI;
use QUI\Update;
use QUI\Utils\Text\XML;

/**
 * An installed package
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event  onPackageSetup [ this ]
 * @event  onPackageInstall [ this ]
 * @event  onPackageUninstall [ string PackageName ]
 */
class Package extends QUI\QDOM
{
    /**
     * Name of the package
     *
     * @var string
     */
    protected $name = '';

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
    protected $configPath = '';

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
     * constructor
     *
     * @param string $package - Name of the Package
     *
     * @throws \QUI\Exception
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR.$package.'/';

        if (strpos($package, '-asset/') !== false) {
            $packageDir = OPT_DIR.'/bin/'.explode('/', $package)[1].'/';
        }

        if (!is_dir($packageDir)) {
            throw new QUI\Exception('Package not exists', 404);
        }

        $this->packageDir = $packageDir;
        $this->name       = $package;

        // no composer.json, no real package
        if (!file_exists($packageDir.'composer.json')) {
            return;
        }

        $this->composerData = json_decode(
            file_get_contents($packageDir.'composer.json'),
            true
        );

        if (!isset($this->composerData['type'])) {
            return;
        }

        if (strpos($this->composerData['type'], 'quiqqer-') === false) {
            return;
        }

        $this->isQuiqqerPackage = true;
        $this->configPath       = CMS_DIR.'etc/plugins/'.$this->getName().'.ini.php';

        QUI\Utils\System\File::mkfile($this->configPath);
    }

    /**
     * Read the package xml
     *
     * @return array
     */
    protected function getPackageXMLData()
    {
        if (!$this->isQuiqqerPackage()) {
            return array();
        }

        if (!is_null($this->packageXML)) {
            return $this->packageXML;
        }

        $packageXML = $this->packageDir.'/package.xml';

        // package xml
        if (!file_exists($packageXML)) {
            $this->packageXML = array();

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
            return array();
        }

        if ($providerName === false) {
            return $packageData['provider'];
        }

        $provider = $packageData['provider'];
        $provider = array_filter($provider, function ($key) use ($providerName) {
            return $key === $providerName;
        }, \ARRAY_FILTER_USE_KEY);

        if (!isset($provider[$providerName])) {
            return array();
        }

        return $provider[$providerName];
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
        $packageData = $this->getPackageXMLData();

        if (isset($packageData['title']) && !empty($packageData['title'])) {
            return $packageData['title'];
        }

        if ($this->isQuiqqerPackage()
            && QUI::getLocale()->exists($this->name, 'package.title')
        ) {
            return QUI::getLocale()->get($this->name, 'package.title');
        }

        return $this->getName();
    }

    /**
     * Return the package description
     *
     * @return String
     */
    public function getDescription()
    {
        $packageData = $this->getPackageXMLData();

        if (isset($packageData['description'])) {
            return $packageData['description'];
        }

        if ($this->isQuiqqerPackage()
            && QUI::getLocale()->exists($this->name, 'package.description')
        ) {
            return QUI::getLocale()->get($this->name, 'package.description');
        }

        $composer = $this->getComposerData();

        if (isset($composer['description'])) {
            return $composer['description'];
        }

        return '';
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

        if (file_exists($this->packageDir.'bin/package.png')) {
            return str_replace(OPT_DIR, URL_OPT_DIR, $this->packageDir).'bin/package.png';
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
        $nameShortCut = preg_replace("/[^A-Za-z0-9 ]/", '', $this->getName());

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

        if (!isset($packageData['preview']) || !is_array($packageData['preview'])) {
            return array();
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

        if (file_exists($this->packageDir.'composer.json')) {
            $this->composerData = json_decode(
                file_get_contents($this->packageDir.'composer.json'),
                true
            );
        }

        if (file_exists($this->packageDir.'package.json')) {
            $this->composerData = json_decode(
                file_get_contents($this->packageDir.'package.json'),
                true
            );
        }

        if (file_exists($this->packageDir.'bower.json')) {
            $this->composerData = json_decode(
                file_get_contents($this->packageDir.'bower.json'),
                true
            );
        }

        return array();
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

        return array();
    }

    /**
     * Get specific XML file from Package
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     * @return string|false - absolute file path or false if xml file does not exist
     */
    public function getXMLFile($name)
    {
        $file = $this->getDir().$name;

        if (!file_exists($file)) {
            return false;
        }

        return $file;
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
    public function setup($params = array())
    {
        QUI::getEvents()->fireEvent('packageSetupBegin', array($this));

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
            QUI::getEvents()->fireEvent('packageSetupEnd', array($this));

            return;
        }

        // permissions
        if ($this->getName() != 'quiqqer/quiqqer') { // you can't set permissions to the core
            try {
                $found = QUI::getDataBase()->fetch(array(
                    'from'  => QUI\Permissions\Manager::table(),
                    'where' => array(
                        'name' => $this->getPermissionName()
                    ),
                    'limit' => 1
                ));

                if (!isset($found[0])) {
                    QUI::getPermissionManager()->addPermission(array(
                        'name'         => $this->getPermissionName(),
                        'title'        => 'quiqqer/quiqqer permission.package.canUse',
                        'desc'         => '',
                        'area'         => '',
                        'type'         => 'bool',
                        'defaultvalue' => 1
                    ));
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }


            $languages = QUI\Translator::getAvailableLanguages();

            $data = array(
                'datatype' => 'php,js',
                'package'  => $this->getName()
            );

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
        Update::importDatabase($dir.'database.xml');
        Update::importTemplateEngines($dir.'engines.xml');
        Update::importEditors($dir.'wysiwyg.xml');

        QUI::getPermissionManager()->deletePermissionsFromPackage($this);

        Update::importPermissions($dir.'permissions.xml', $this->getName());
        Update::importMenu($dir.'menu.xml');

        // events
        QUI\Events\Manager::clear($this->getName());
        Update::importEvents($dir.'events.xml', $this->getName());
        Update::importSiteEvents($dir.'site.xml');

        // locale
        if ($optionLocaleImport) {
            QUI\Translator::importFromPackage($this, true, true, $optionForceImport);
        }

        if ($optionLocalePublish) {
            $this->setupLocalePublish();
        }


        // settings
        if (!file_exists($dir.'settings.xml')) {
            QUI::getEvents()->fireEvent('packageSetup', array($this));
            QUI\Cache\Manager::clearAll();

            QUI::getEvents()->fireEvent('packageSetupEnd', array($this));

            return;
        }

        // $defaults = XML::getConfigParamsFromXml( $dir .'settings.xml' );
        $Config = XML::getConfigFromXml($dir.'settings.xml');

        if ($Config) {
            $Config->save();
        }

        QUI::getEvents()->fireEvent('packageSetup', array($this));

        QUI\Cache\Manager::clearAll();
        QUI::getEvents()->fireEvent('packageSetupEnd', array($this));
    }

    /**
     * publish the locale files of the package
     */
    protected function setupLocalePublish()
    {
        $dir = $this->getDir();

        try {
            $groups   = array();
            $files    = [$dir.'locale.xml'];
            $Dom      = XML::getDomFromXml($dir.'locale.xml');
            $FileList = $Dom->getElementsByTagName('file');

            if ($FileList->length) {
                /** @var \DOMElement $File */
                foreach ($FileList as $File) {
                    $files[] = $this->getDir().ltrim($File->getAttribute('file'), '/');
                }
            }

            foreach ($files as $file) {
                $groups = XML::getLocaleGroupsFromDom(
                    XML::getDomFromXml($file)
                );
            }

            $groups = array_map(function ($data) {
                return $data['group'];
            }, $groups);

            $groups = array_unique($groups);
        } catch (\Exception $Exception) {
            $groups = array();
            QUI\System\Log::addWarning($Exception->getMessage());
        }


        $groups[] = $this->getName();
        $groups[] = 'quiqqer/quiqqer';

        $groups = array_unique($groups);

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
        return $this->isQuiqqerPackage;
    }

    /**
     * Execute first install
     *
     * @throws QUI\Exception
     */
    public function install()
    {
        QUI::getEvents()->fireEvent('packageInstall', array($this));
    }

    /**
     * Uninstall the package / plugin
     * it doesn't destroy the database data, its only uninstall the package
     *
     * @throws QUI\Exception
     */
    public function uninstall()
    {
        QUI::getEvents()->fireEvent('packageUnInstall', array($this->getName()));

        // remove events
        QUI::getEvents()->removePackageEvents($this);
    }

    /**
     * Destroy the complete package / plugin
     * it destroy the database data, too
     *
     * @todo implementieren
     * @throws QUI\Exception
     */
    public function destroy()
    {
        QUI::getPermissionManager()->removePermission($this->getPermissionName());
        QUI::getPermissionManager()->removePermission($this->getPermissionName('header'));

        QUI::getEvents()->fireEvent('packageDestroy', array($this->getName()));
    }

    /**
     * event on update
     *
     * @throws QUI\Exception
     */
    public function onUpdate()
    {
        QUI::getEvents()->fireEvent('packageUpdate', array($this));
    }
}
