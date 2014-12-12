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
 *
 * @event onPackageSetup [ this ]
 * @event onPackageInstall [ this ]
 * @event onPackageUninstall [ String PackageName ]
 */
class Package extends QUI\QDOM
{
    /**
     * Name of the package
     * @var String
     */
    protected $_name = '';

    /**
     * Directory of the package
     * @var String
     */
    protected $_packageDir = '';

    /**
     * Path to the Config
     * @var String
     */
    protected $_configPath = '';

    /**
     * Package Config
     * @var QUI\Config
     */
    protected $_Config = null;

    /**
     * constructor
     *
     * @param String $package - Name of the Package
     * @throws QUI\Exception
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR . $package .'/';

        if ( !is_dir( $packageDir ) ) {
            throw new QUI\Exception( 'Package not exists', 404 );
        }

        $this->_packageDir = $packageDir;
        $this->_name       = $package;

        // no composer.json, no real package
        if ( !file_exists( $packageDir .'composer.json' ) ) {
            return;
        }

        $composer = json_decode( file_get_contents( $packageDir .'composer.json' ), true );

        if ( !isset( $composer['type'] ) ) {
            return;
        }

        if ( strpos( $composer['type'], 'quiqqer-') === false ) {
            return;
        }

        $this->_configPath = CMS_DIR .'etc/plugins/'. $this->getName() .'.ini.php';

        if ( !file_exists( $this->_configPath ) ) {
            file_put_contents( $this->_configPath , '' );
        }
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
        if ( empty( $this->_configPath ) ) {
            return false;
        }

        if ( !$this->_Config ) {
            $this->_Config = new QUI\Config( $this->_configPath );
        }

        return $this->_Config;
    }

    /**
     * Execute the package setup
     */
    public function setup()
    {
        $dir = $this->getDir();

        Update::importDatabase( $dir .'database.xml' );
        Update::importTemplateEngines( $dir .'engines.xml' );
        Update::importEditors( $dir .'wysiwyg.xml' );
        Update::importMenu( $dir .'menu.xml' );
        Update::importPermissions( $dir .'permissions.xml', $this->getName() );
        Update::importMenu( $dir .'menu.xml' );

        // events
        Update::importEvents( $dir .'events.xml' );
        Update::importSiteEvents( $dir .'site.xml' );

        // settings
        if ( !file_exists( $dir .'settings.xml' ) )
        {
            QUI::getEvents()->fireEvent( 'packageSetup', array( $this ) );
            return;
        }

        // $defaults = XML::getConfigParamsFromXml( $dir .'settings.xml' );
        $Config = XML::getConfigFromXml( $dir .'settings.xml' );

        if ( $Config ) {
            $Config->save();
        }

        QUI::getEvents()->fireEvent( 'packageSetup', array( $this ) );
    }

    /**
     * Execute first install
     */
    public function install()
    {
        $this->setup();

        QUI::getEvents()->fireEvent( 'packageInstall', array( $this ) );
    }

    /**
     * Uninstall the package / plugin
     * it doesn't destroy the database data, its only uninstall the package
     */
    public function uninstall()
    {


        QUI::getEvents()->fireEvent( 'packageUninstall', array( $this->getName() ) );
    }

    /**
     * Destroy the complete package / plugin
     * it destroy the database data, too
     */
    public function destroy()
    {

        QUI::getEvents()->fireEvent( 'packageUninstall', array( $this->getName() ) );
    }
}
