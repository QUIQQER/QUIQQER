<?php

/**
 * This file contains \QUI\System\VhostManager
 */

namespace QUI\System;

use QUI;
use QUI\Config;
use QUI\Utils\Security\Orthos;

/**
 * Virtual Host Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 * @todo vhosts permissions
 */

class VhostManager
{
    /**
     * Config
     * @var \QUI\Config
     */
    protected $_Config = null;

    /**
     * Return the config
     * @return \QUI\Config
     */
    protected function _getConfig()
    {
        if ( !file_exists( CMS_DIR .'/etc/vhosts.ini.php' ) ) {
            file_put_contents( CMS_DIR .'/etc/vhosts.ini.php' , '' );
        }

        $this->_Config = new Config( CMS_DIR .'/etc/vhosts.ini.php' );

        return $this->_Config;
    }

    /**
     * Check the vhosts entry and tries to repair it
     * eq. search empty language entries
     */
    public function repair()
    {
        $Config = $this->_getConfig();
        $list   = $this->getList();

        // check lang entries
        foreach ( $list as $host => $data )
        {
            if ( !isset( $data[ 'project' ] ) ) {
                continue;
            }

            if ( !isset( $data[ 'lang' ] ) ) {
                continue;
            }

            if ( !isset( $data[ 'template' ] ) ) {
                continue;
            }

            $Project = \QUI::getProject( $data[ 'project' ] );
            $langs   = $Project->getAttribute( 'langs' );

            foreach ( $langs as $lang )
            {
                if ( isset( $data[ $lang ] ) && !empty( $data[ $lang ] ) ) {
                    continue;
                }

                // repair language entry
                $Config->setValue( $host, $lang, $this->getHostByProject(
                    $data[ 'project' ],
                    $lang
                ) );
            }
        }


        $Config->save();
    }

    /**
     * Return the vhost list
     *
     * @return array
     */
    public function getList()
    {
        return $this->_getConfig()->toArray();
    }

    /**
     * Add a vhost
     *
     * @param String $vhost - host name (eq: www.something.com)
     * @throws \QUI\Exception
     */
    public function addVhost($vhost)
    {
        $Config = $this->_getConfig();

        if ( $Config->existValue( $vhost ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.vhost.exist'
                )
            );
        }

        $Config->setSection( $vhost, array() );
        $Config->save();
    }

    /**
     * Add or edit a vhost entry
     *
     * @param String $vhost - host name (eq: www.something.com)
     * @param Array $data - data of the host
     * @throws \QUI\Exception
     */
    public function editVhost($vhost, array $data)
    {
        $Config = $this->_getConfig();

        if ( !$Config->existValue( $vhost ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.vhost.not.found'
                )
            );
        }

        // daten prüfen
        $result = array();

        foreach ( $data as $key => $value )
        {
            $key = Orthos::clear( $key );

            $result[ $key ] = $value;
        }

        // lang hosts
        $Project      = QUI::getProject( $result['project'] );
        $projectLangs = $Project->getAttribute( 'langs' );
        $lang         = $result['lang'];

        foreach ( $projectLangs as $projectLang )
        {
            if ( !isset( $result[ $projectLang ] ) ) {
                $result[ $projectLang ] = '';
            }

            if ( !empty( $result[ $projectLang ] ) ) {
                continue;
            }

            $result[ $projectLang ] = $this->getHostByProject(
                $result['project'],
                $projectLang
            );
        }

        if ( !isset( $result[ $lang ] ) || empty( $result[ $lang ] ) ) {
            $result[ $lang ] = $vhost;
        }

        $Config->setSection( $vhost, $result );
        $Config->save();

        $this->repair();
    }

    /**
     * Remove a vhost entry
     *
     * @param String $vhost
     * @throws \QUI\Exception
     */
    public function removeVhost($vhost)
    {
        $Config = $this->_getConfig();

        if ( !$Config->existValue( $vhost ) )
        {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'system',
                    'exception.vhost.not.found'
                )
            );
        }

        $Config->del( $vhost );
        $Config->save();
    }

    /**
     * Return the vhost data
     *
     * @param String $vhost
     * @return Array|false
     */
    public function getVhost($vhost)
    {
        return $this->_getConfig()->getSection( $vhost );
    }

    /**
     * Return the host, is a host is set for a project
     *
     * @param String $projectName - Name of the project
     * @param String $projectLang - Language of the project (de, en, etc...)
     * @return String
     */
    public function getHostByProject($projectName, $projectLang)
    {
        $config = $this->getList();

        foreach ( $config as $host => $data )
        {
            if ( !isset( $data[ 'project' ] ) ) {
                continue;
            }

            if ( !isset( $data[ 'lang' ] ) ) {
                continue;
            }

            if ( $data[ 'project' ] != $projectName ) {
                continue;
            }

            if ( $data[ 'lang' ] != $projectLang ) {
                continue;
            }

            return $host;
        }

        return '';
    }

    /**
     * Return all hosts from the project
     *
     * @param String $projectName - Name of the project
     * @return Array
     */
    public function getHostsByProject($projectName)
    {
        $config = $this->getList();
        $list   = array();

        foreach ( $config as $host => $data )
        {
            if ( $data[ 'project' ] == $projectName ) {
                $list[] = $host;
            }
        }

        return $list;
    }
}
