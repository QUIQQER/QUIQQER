<?php

/**
 * This file contains namespace QUI\Package\Package
 */

namespace QUI\Package;

/**
 * An installed package
 *
 * @author www.pcsg.de
 */

class Package extends \QUI\QDOM
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
     * @var \QUI\Config
     */
    protected $_Config = null;

    /**
     * constructor
     *
     * @param String $package - Name of the Package
     */
    public function __construct($package)
    {
        $packageDir = OPT_DIR . $package;

        if ( is_dir( $package ) ) {
            throw new \QUI\Exception( 'Package not exists', 404 );
        }

        $this->_packageDir = $packageDir;
        $this->_name       = $package;

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
     */
    public function getConfig()
    {
        if ( !$this->_Config ) {
            $this->_Config = new \QUI\Config( $this->_configPath );
        }

        return $this->_Config;
    }

    /**
     * Execute the package setup
     */
    public function setup()
    {
        \QUI::getPackageManager()->setup( $this->getName() );
    }
}
