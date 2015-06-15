<?php

/**
 * This file contains namespace QUI\Package\Package
 */

namespace QUI\Package;

use QUI;
use QUI\Update;
use QUI\Utils\XML;

/**
 * An installed package
 *
 * @author www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @event  onPackageSetup [ this ]
 * @event  onPackageInstall [ this ]
 * @event  onPackageUninstall [ String PackageName ]
 */
class Package extends QUI\QDOM
{
    /**
     * Name of the package
     *
     * @var string
     */
    protected $_name = '';

    /**
     * Directory of the package
     *
     * @var string
     */
    protected $_packageDir = '';

    /**
     * Package composer data from the composer file
     *
     * @var bool|array
     */
    protected $_composerData = false;

    /**
     * Path to the Config
     *
     * @var string
     */
    protected $_configPath = '';

    /**
     * Package Config
     *
     * @var QUI\Config
     */
    protected $_Config = null;

    /**
     * constructor
     *
     * @param String $package - Name of the Package
     *
     * @throws QUI\Exception
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR.$package.'/';

        if (!is_dir($packageDir)) {
            throw new QUI\Exception('Package not exists', 404);
        }

        $this->_packageDir = $packageDir;
        $this->_name = $package;

        // no composer.json, no real package
        if (!file_exists($packageDir.'composer.json')) {
            return;
        }

        $this->_composerData = json_decode(
            file_get_contents($packageDir.'composer.json'),
            true
        );

        if (!isset($this->_composerData['type'])) {
            return;
        }

        if (strpos($this->_composerData['type'], 'quiqqer-') === false) {
            return;
        }

        $this->_configPath = CMS_DIR.'etc/plugins/'.$this->getName().'.ini.php';

        QUI\Utils\System\File::mkfile($this->_configPath);
    }

    /**
     * Return the system path of the package
     *
     * @return String
     */
    public function getDir()
    {
        return $this->_packageDir;
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
     * @return String
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Return the package config
     *
     * @return QUI\Config|Bool
     */
    public function getConfig()
    {
        if (empty($this->_configPath)) {
            return false;
        }

        if (!$this->_Config) {
            $this->_Config = new QUI\Config($this->_configPath);
        }

        return $this->_Config;
    }

    /**
     * Return the composer data of the package
     *
     * @return array|bool|mixed
     * @throws QUI\Exception
     */
    public function getComposerData()
    {
        if ($this->_composerData) {
            return $this->_composerData;
        }

        $composer = QUI::getPackageManager()->show($this->getName());

        if (!isset($composer['name'])) {
            $composer['name'] = $this->getName();
        }

        $this->_composerData = $composer;

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

        Update::importDatabase($dir.'database.xml');
        Update::importTemplateEngines($dir.'engines.xml');
        Update::importEditors($dir.'wysiwyg.xml');
        Update::importMenu($dir.'menu.xml');
        Update::importPermissions($dir.'permissions.xml', $this->getName());
        Update::importMenu($dir.'menu.xml');

        // events
        Update::importEvents($dir.'events.xml');
        Update::importSiteEvents($dir.'site.xml');

        Update::importLocale($dir.'locale.xml');

        // settings
        if (!file_exists($dir.'settings.xml')) {
            QUI::getEvents()->fireEvent('packageSetup', array($this));

            return;
        }

        // $defaults = XML::getConfigParamsFromXml( $dir .'settings.xml' );
        $Config = XML::getConfigFromXml($dir.'settings.xml');

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
     */
    public function uninstall()
    {


        QUI::getEvents()
           ->fireEvent('packageUninstall', array($this->getName()));
    }

    /**
     * Destroy the complete package / plugin
     * it destroy the database data, too
     */
    public function destroy()
    {

        QUI::getEvents()
           ->fireEvent('packageUninstall', array($this->getName()));
    }
}
