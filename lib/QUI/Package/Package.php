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
     * constructor
     *
     * @param string $package - Name of the Package
     *
     * @throws QUI\Exception
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR . $package . '/';

        if (!is_dir($packageDir)) {
            throw new QUI\Exception('Package not exists', 404);
        }

        $this->packageDir = $packageDir;
        $this->name       = $package;

        // no composer.json, no real package
        if (!file_exists($packageDir . 'composer.json')) {
            return;
        }

        $this->composerData = json_decode(
            file_get_contents($packageDir . 'composer.json'),
            true
        );

        if (!isset($this->composerData['type'])) {
            return;
        }

        if (strpos($this->composerData['type'], 'quiqqer-') === false) {
            return;
        }

        $this->configPath = CMS_DIR . 'etc/plugins/' . $this->getName() . '.ini.php';

        QUI\Utils\System\File::mkfile($this->configPath);
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
     * Return the var dir for the package
     * you can use the var dir for not accessible files
     *
     * @return string
     */
    public function getVarDir()
    {
        $varDir = VAR_DIR . 'package/' . $this->getName() . '/';

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
     * Return the package config
     *
     * @return QUI\Config|boolean
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
     * Return the composer data of the package
     *
     * @return array|bool|mixed
     * @throws QUI\Exception
     */
    public function getComposerData()
    {
        if ($this->composerData) {
            return $this->composerData;
        }

        $composer = QUI::getPackageManager()->show($this->getName());

        if (!isset($composer['name'])) {
            $composer['name'] = $this->getName();
        }

        $this->composerData = $composer;

        return $composer;
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
     * Execute the package setup
     */
    public function setup()
    {
        $dir = $this->getDir();

        Update::importDatabase($dir . 'database.xml');
        Update::importTemplateEngines($dir . 'engines.xml');
        Update::importEditors($dir . 'wysiwyg.xml');
        Update::importMenu($dir . 'menu.xml');
        Update::importPermissions($dir . 'permissions.xml', $this->getName());
        Update::importMenu($dir . 'menu.xml');

        // events
        QUI\Events\Manager::clear($this->getName());
        Update::importEvents($dir . 'events.xml', $this->getName());
        Update::importSiteEvents($dir . 'site.xml');

        Update::importLocale($dir . 'locale.xml');
        QUI\Translator::publish($this->getName());

        // settings
        if (!file_exists($dir . 'settings.xml')) {
            QUI::getEvents()->fireEvent('packageSetup', array($this));
            return;
        }

        // $defaults = XML::getConfigParamsFromXml( $dir .'settings.xml' );
        $Config = XML::getConfigFromXml($dir . 'settings.xml');

        if ($Config) {
            $Config->save();
        }

        QUI::getEvents()->fireEvent('packageSetup', array($this));
    }

    /**
     * Execute first install
     */
    public function install()
    {
        $this->setup();

        QUI\Cache\Manager::clearAll();
        QUI::getEvents()->fireEvent('packageInstall', array($this));
    }

    /**
     * Uninstall the package / plugin
     * it doesn't destroy the database data, its only uninstall the package
     *
     * @todo implementieren
     */
    public function uninstall()
    {
        QUI::getEvents()
            ->fireEvent('packageUninstall', array($this->getName()));
    }

    /**
     * Destroy the complete package / plugin
     * it destroy the database data, too
     *
     * @todo implementieren
     */
    public function destroy()
    {
        QUI::getEvents()
            ->fireEvent('packageUninstall', array($this->getName()));
    }
}
