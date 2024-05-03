<?php

/**
 * This file contains namespace QUI\Package\Package
 */

namespace QUI\Package;

use DOMElement;
use Exception;
use QUI;
use QUI\Cache\LongTermCache;
use QUI\Config;
use QUI\Update;
use QUI\Utils\Text\XML;
use Seld\JsonLint\JsonParser;
use Seld\JsonLint\ParsingException;

use function array_filter;
use function array_map;
use function array_unique;
use function explode;
use function file_exists;
use function htmlspecialchars;
use function is_array;
use function is_dir;
use function json_last_error_msg;
use function ltrim;
use function preg_replace;
use function str_replace;

use const ARRAY_FILTER_USE_KEY;

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
    const PANEL_XML = 'panel.xml';

    /**
     * Name of the package
     *
     * @var string
     */
    protected string $name = '';

    /**
     * Title of the package
     *
     * @var ?string
     */
    protected ?string $title = null;

    /**
     * Description of the package
     *
     * @var string|null
     */
    protected ?string $description = null;

    /**
     * Directory of the package
     *
     * @var string
     */
    protected string $packageDir = '';

    /**
     * @var array|null
     */
    protected ?array $packageXML = null;

    /**
     * Package composer data from the composer file
     *
     * @var bool|array
     */
    protected array|bool $composerData = false;

    /**
     * Path to the Config
     *
     * @var ?string
     */
    protected ?string $configPath = null;

    /**
     * Package Config
     *
     * @var ?QUI\Config
     */
    protected ?QUI\Config $Config = null;

    /**
     * @var bool
     */
    protected bool $isQuiqqerPackage = false;

    /**
     * @var bool
     */
    protected bool $readPackageInfo = false;

    /**
     * constructor
     *
     * @param string $package - Name of the Package
     *
     * @throws QUI\Exception
     */
    public function __construct(string $package)
    {
        $packageDir = OPT_DIR . $package . '/';

        // if not exists look at bin
        if (!is_dir($packageDir) && str_contains($package, '/')) {
            $packageDir = OPT_DIR . '/bin/' . explode('/', $package)[1] . '/';
        }

        if (!is_dir($packageDir)) {
            $package = htmlspecialchars($package);
            throw new QUI\Exception('Package not exists [' . $package . ']', 404);
        }

        $this->packageDir = $packageDir;
        $this->name = $package;
    }

    /**
     * Alias for getCacheName()
     *
     * @return string
     */
    public function getCachePath(): string
    {
        return $this->getCacheName();
    }

    /**
     * Return the cache name for this package
     *
     * @return string
     */
    public function getCacheName(): string
    {
        return 'quiqqer/package/' . $this->getName();
    }

    /**
     * Return the name of the package
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Return all providers
     *
     * @param bool|string $providerName - optional, Name of the wanted providers
     * @return array
     *
     * @todo cache that
     */
    public function getProvider(bool|string $providerName = false): array
    {
        $packageData = $this->getPackageXMLData();

        if (empty($packageData['provider'])) {
            return [];
        }

        if ($providerName === false) {
            return $packageData['provider'];
        }

        $provider = $packageData['provider'];
        $provider = array_filter($provider, function ($key) use ($providerName) {
            return $key === $providerName;
        }, ARRAY_FILTER_USE_KEY);

        if (!isset($provider[$providerName])) {
            return [];
        }

        return $provider[$providerName];
    }

    /**
     * Read the package xml
     *
     * @return array
     */
    protected function getPackageXMLData(): array
    {
        if ($this->packageXML !== null) {
            return $this->packageXML;
        }

        if (!$this->isQuiqqerPackage()) {
            $this->packageXML = [];

            return [];
        }

        $packageXML = $this->packageDir . '/package.xml';

        // package xml
        if (!file_exists($packageXML)) {
            $this->packageXML = [];

            return $this->packageXML;
        }

        $this->packageXML = XML::getPackageFromXMLFile($packageXML);

        return $this->packageXML;
    }

    /**
     * Is the package a quiqqer package?
     *
     * @return bool
     */
    public function isQuiqqerPackage(): bool
    {
        $this->readPackageData();

        if (!isset($this->composerData['type'])) {
            return false;
        }

        return $this->isQuiqqerPackage;
    }

    /**
     * read the package data
     */
    protected function readPackageData(): void
    {
        if ($this->readPackageInfo) {
            return;
        }

        // no composer.json, no real package
        if (!file_exists($this->packageDir . 'composer.json')) {
            $this->readPackageInfo = true;

            return;
        }

        $this->getComposerData();

        // ERROR
        if (!$this->composerData) {
            QUI\System\Log::addCritical(
                'Package composer.json has some errors: ' . json_last_error_msg(),
                [
                    'package' => $this->name,
                    'packageDir' => $this->packageDir
                ]
            );
        }

        if (!isset($this->composerData['type'])) {
            $this->readPackageInfo = true;

            return;
        }

        if ($this->composerData['type'] === 'quiqqer-asset') {
            $this->readPackageInfo = true;

            return;
        }

        if (!str_contains($this->composerData['type'], 'quiqqer-')) {
            $this->readPackageInfo = true;

            return;
        }

        $this->isQuiqqerPackage = true;
        $this->configPath = CMS_DIR . 'etc/plugins/' . $this->getName() . '.ini.php';

        QUI\Utils\System\File::mkfile($this->configPath);


        $this->readPackageInfo = true;
    }

    /**
     * Return the composer data of the package
     *
     * @return array|bool|mixed
     */
    public function getComposerData(): mixed
    {
        if (!empty($this->composerData)) {
            return $this->composerData;
        }

        $cache = $this->getCacheName() . '/composerData';

        try {
            $this->composerData = LongTermCache::get($cache);

            return $this->composerData;
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::writeDebugException($Exception);
        }

        $Parser = new JsonParser();
        $file = false;

        if (file_exists($this->packageDir . 'composer.json')) {
            $file = $this->packageDir . 'composer.json';
        } elseif (file_exists($this->packageDir . 'package.json')) {
            $file = $this->packageDir . 'package.json';
        } elseif (file_exists($this->packageDir . 'bower.json')) {
            $file = $this->packageDir . 'bower.json';
        }

        if ($file) {
            try {
                $this->composerData = $Parser->parse(
                    file_get_contents($file),
                    JsonParser::PARSE_TO_ASSOC
                );
            } catch (ParsingException $Exception) {
                QUI\System\Log::addAlert($Exception->getMessage(), [
                    'ALERT' => 'FILE HAS PARSING ERRORS',
                    'jsonfile' => $file
                ]);
            }
        }

        $lock = QUI::getPackageManager()->getPackageLock($this);

        if (isset($lock['version'])) {
            $this->composerData['version'] = $lock['version'];
        }

        LongTermCache::set($cache, $this->composerData);

        return $this->composerData;
    }

    /**
     * Has the package a template parent?
     * If the package is a template, it's possible that the template has a package
     *
     * @return bool
     */
    public function hasTemplateParent(): bool
    {
        $parent = $this->getTemplateParent();

        return !empty($parent);
    }

    /**
     * Return the template parent
     * - if one is set
     *
     * @return bool|Package
     */
    public function getTemplateParent(): Package|bool
    {
        $packageData = $this->getPackageXMLData();

        if (empty($packageData['template_parent'])) {
            return false;
        }

        try {
            return QUI::getPackage($packageData['template_parent']);
        } catch (QUI\Exception) {
            return false;
        }
    }

    /**
     * Return the var dir for the package
     * you can use the var dir for not accessible files
     *
     * @return string
     */
    public function getVarDir(): string
    {
        $varDir = VAR_DIR . 'package/' . $this->getName() . '/';

        QUI\Utils\System\File::mkdir($varDir);

        return $varDir;
    }

    /**
     * Return the package title
     *
     * @return string
     */
    public function getTitle(): string
    {
        if ($this->title) {
            return $this->title;
        }

        $packageData = $this->getPackageXMLData();

        if (!empty($packageData['title'])) {
            $this->title = $packageData['title'];

            return $this->title;
        }

        if (
            $this->isQuiqqerPackage()
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
     * @return string
     */
    public function getDescription(): string
    {
        if ($this->description) {
            return $this->description;
        }

        $packageData = $this->getPackageXMLData();

        if (isset($packageData['description'])) {
            $this->description = $packageData['description'];

            return $this->description;
        }

        if (
            $this->isQuiqqerPackage()
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
     * @return string
     */
    public function getImage(): string
    {
        $packageData = $this->getPackageXMLData();

        if (isset($packageData['image'])) {
            return $packageData['image'];
        }

        if (file_exists($this->packageDir . 'bin/package.png')) {
            return str_replace(OPT_DIR, URL_OPT_DIR, $this->packageDir) . 'bin/package.png';
        }

        return '';
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        $composer = $this->getComposerData();

        return $composer['version'] ?? '';
    }

    /**
     * Return all preview images
     * Not the main image
     *
     * @return array
     */
    public function getPreviewImages(): array
    {
        $packageData = $this->getPackageXMLData();

        if (!isset($packageData['preview']) || !is_array($packageData['preview'])) {
            return [];
        }

        return $packageData['preview'];
    }

    /**
     * Return the package config
     *
     * @return Config|null
     *
     * @throws QUI\Exception
     */
    public function getConfig(): ?QUI\Config
    {
        if ($this->configPath === null) {
            $configFile = CMS_DIR . 'etc/plugins/' . $this->getName() . '.ini.php';

            if (file_exists($configFile)) {
                $this->configPath = $configFile;
            }
        }

        if (empty($this->configPath)) {
            return null;
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
    public function getLock(): array
    {
        return QUI::getPackageManager()->getPackageLock($this);
    }

    /**
     * Clears the package cache
     */
    public function clearCache(): void
    {
        LongTermCache::clear($this->getCacheName());
    }

    /**
     * Return the requirements / dependencies of the package
     *
     * @return array
     */
    public function getDependencies(): array
    {
        $composer = $this->getComposerData();

        return $composer['require'] ?? [];
    }

    /**
     * use getXMLFilePath()
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     * @return string|false - absolute file path or false if xml file does not exist
     * @deprecated
     */
    public function getXMLFile(string $name)
    {
        return $this->getXMLFilePath($name);
    }

    /**
     * Get specific XML file from Package
     *
     * @param string $name - e.g. "database.xml" / "package.xml" etc.
     * @return string|false - absolute file path or false if xml file does not exist
     */
    public function getXMLFilePath(string $name): bool|string
    {
        $file = $this->getDir() . $name;

        if (!file_exists($file)) {
            return false;
        }

        return $file;
    }

    /**
     * Return the system path of the package
     *
     * @return string
     */
    public function getDir(): string
    {
        return $this->packageDir;
    }

    /**
     * Checks the package permission
     *
     * @param string $permission - could be canUse
     * @param QUI\Interfaces\Users\User|null $User
     *
     * @return bool
     */
    public function hasPermission(string $permission = 'canUse', QUI\Interfaces\Users\User $User = null): bool
    {
        if (!QUI::conf('permissions', 'package')) {
            return true;
        }

        return QUI\Permissions\Permission::hasPermission(
            $this->getPermissionName($permission),
            $User
        );
    }

    /**
     * Return the permission name for a package permission
     * eq:
     * - canUse
     *
     * @param string $permissionName
     * @return mixed
     */
    public function getPermissionName(string $permissionName = 'canUse'): string
    {
        $nameShortCut = preg_replace("/[^A-Za-z0-9 ]/", '', $this->getName());

        return match ($permissionName) {
            'header' => 'permission.quiqqer.packages.' . $nameShortCut . '._header',
            default => 'quiqqer.packages.' . $nameShortCut . '.canUse',
        };
    }

    /**
     * Execute first install
     *
     * @throws QUI\Exception
     */
    public function install(): void
    {
        $this->readPackageData();

        $pkgName = $this->getName();

        QUI::getEvents()->fireEvent('packageInstallBefore', [$this]);
        QUI::getEvents()->fireEvent('packageInstallBefore-' . $pkgName, [$this]);

        Update::importEvents(
            $this->getDir() . self::EVENTS_XML,
            $this->getName()
        );

        QUI::getEvents()->fireEvent('packageInstall', [$this]);
        QUI::getEvents()->fireEvent('packageInstall-' . $pkgName, [$this]);

        if ($this->isQuiqqerPackage()) {
            $this->setup();
        }

        $this->moveQuiqqerAsset();


        QUI::getEvents()->fireEvent('packageInstallAfter', [$this]);
        QUI::getEvents()->fireEvent('packageInstallAfter-' . $pkgName, [$this]);
    }

    /**
     * Execute the package setup
     *
     * @param array $params - optional ['localePublish' => true, 'localeImport' => true, 'forceImport' => false]
     * @throws QUI\Exception
     */
    public function setup(array $params = []): void
    {
        $this->readPackageData();

        $pkgName = $this->getName();

        QUI::getEvents()->fireEvent('packageSetupBegin', [$this]);
        QUI::getEvents()->fireEvent('packageSetupBegin-' . $pkgName, [$this]);

        // options
        $optionLocalePublish = true;
        $optionLocaleImport = true;
        $optionForceImport = false;

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

        if ($this->isQuiqqerAsset()) {
            $this->moveQuiqqerAsset();
        }

        if (!$this->isQuiqqerPackage()) {
            QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);
            QUI::getEvents()->fireEvent('packageSetupEnd-' . $pkgName, [$this]);

            return;
        }

        // permissions
        if ($this->getName() !== 'quiqqer/quiqqer') { // you can't set permissions to the core
            try {
                $found = QUI::getDataBase()->fetch([
                    'from' => QUI\Permissions\Manager::table(),
                    'where' => [
                        'name' => $this->getPermissionName()
                    ],
                    'limit' => 1
                ]);

                if (!isset($found[0])) {
                    QUI::getPermissionManager()->addPermission([
                        'name' => $this->getPermissionName(),
                        'title' => 'quiqqer/quiqqer permission.package.canUse',
                        'desc' => '',
                        'area' => '',
                        'type' => 'bool',
                        'defaultvalue' => 0,
                        'rootPermission' => 1
                    ]);
                }
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
            }


            $languages = QUI\Translator::getAvailableLanguages();

            $data = [
                'datatype' => 'php,js',
                'package' => $this->getName()
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
            } catch (QUI\Exception) {
                try {
                    QUI\Translator::edit(
                        'quiqqer/quiqqer',
                        $this->getPermissionName('header'),
                        $this->getName(),
                        $data
                    );
                } catch (Exception $Exception) {
                    QUI::getMessagesHandler()->addAttention(
                        $Exception->getMessage()
                    );
                }
            }
        }

        // xml
        Update::importDatabase($dir . self::DATABASE_XML);
        Update::importTemplateEngines($dir . 'engines.xml');
        Update::importEditors($dir . 'wysiwyg.xml');

        QUI::getPermissionManager()->deletePermissionsFromPackage($this);

        Update::importPermissions($dir . self::PERMISSIONS_XML, $this->getName());
        Update::importMenu($dir . self::MENU_XML);

        // events
        QUI\Events\Manager::clear($this->getName());
        Update::importEvents($dir . self::EVENTS_XML, $this->getName());
        Update::importSiteEvents($dir . self::SITE_XML);

        // locale
        if ($optionLocaleImport) {
            QUI\Translator::batchImportFromPackage($this);
        }

        if ($optionLocalePublish) {
            $this->setupLocalePublish();
        }

        // settings
        if (!file_exists($dir . self::SETTINGS_XML)) {
            QUI::getEvents()->fireEvent('packageSetup', [$this]);
            QUI::getEvents()->fireEvent('packageSetup-' . $pkgName, [$this]);
            QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);
            QUI::getEvents()->fireEvent('packageSetupEnd-' . $pkgName, [$this]);

            return;
        }

        $Config = XML::getConfigFromXml($dir . self::SETTINGS_XML);

        if ($Config) {
            $Config->save();
        }

        QUI::getEvents()->fireEvent('packageSetup', [$this]);
        QUI::getEvents()->fireEvent('packageSetup-' . $pkgName, [$this]);
        QUI::getEvents()->fireEvent('packageSetupEnd', [$this]);
        QUI::getEvents()->fireEvent('packageSetupEnd-' . $pkgName, [$this]);
    }

    /**
     * Is the package a quiqqer asset package?
     *
     * @return bool
     */
    public function isQuiqqerAsset(): bool
    {
        $this->readPackageData();

        if (!isset($this->composerData['type'])) {
            return false;
        }

        return $this->composerData['type'] === 'quiqqer-asset';
    }

    /**
     * @throws QUI\Exception
     */
    private function moveQuiqqerAsset(): void
    {
        if (!$this->isQuiqqerAsset()) {
            return;
        }

        $quiqqerAssetDir = OPT_DIR . 'bin/' . $this->getName();

        if (is_dir($quiqqerAssetDir)) {
            QUI::getTemp()->moveToTemp($quiqqerAssetDir);
        }

        // copy this to the package bin
        QUI\Utils\System\File::dircopy(
            $this->getDir(),
            $quiqqerAssetDir
        );
    }

    /**
     * publish the locale files of the package
     */
    protected function setupLocalePublish(): void
    {
        $dir = $this->getDir();

        try {
            $files = [$dir . self::LOCALE_XML];
            $Dom = XML::getDomFromXml($dir . self::LOCALE_XML);
            $FileList = $Dom->getElementsByTagName('file');

            if ($FileList->length) {
                /** @var DOMElement $File */
                foreach ($FileList as $File) {
                    $files[] = $this->getDir() . ltrim($File->getAttribute('file'), '/');
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
        } catch (Exception $Exception) {
            $groups = [];
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
     * Uninstall the package / plugin
     * it doesn't destroy the database data, its only uninstall the package
     *
     * @throws QUI\Exception
     */
    public function uninstall(): void
    {
        QUI::getEvents()->fireEvent('packageUnInstall', [$this->getName()]);
        QUI::getEvents()->fireEvent(
            'packageUnInstall-' . $this->getName(),
            [$this->getName()]
        );

        // remove events
        QUI::getEvents()->removePackageEvents($this);
    }

    /**
     * Destroy the complete package / plugin
     * it destroys the database data, too
     *
     * @throws QUI\Exception
     * @todo implementieren
     */
    public function destroy(): void
    {
        QUI::getPermissionManager()->deletePermission($this->getPermissionName());
        QUI::getPermissionManager()->deletePermission($this->getPermissionName('header'));

        QUI::getEvents()->fireEvent('packageDestroy', [$this->getName()]);
        QUI::getEvents()->fireEvent(
            'packageDestroy-' . $this->getName(),
            [$this->getName()]
        );
    }

    /**
     * event on update
     *
     * @throws QUI\Exception
     */
    public function onUpdate(): void
    {
        QUI::getEvents()->fireEvent('packageUpdate', [$this]);
        QUI::getEvents()->fireEvent(
            'packageUpdate-' . $this->getName(),
            [$this]
        );

        $this->moveQuiqqerAsset();
    }
}
