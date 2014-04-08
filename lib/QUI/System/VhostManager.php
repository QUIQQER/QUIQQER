<?php

/**
 * This file contains \QUI\System\VhostManager
 */

namespace QUI\System;

/**
 * Virtual Host Manager
 *
 * @author www.pcsg.de (Henning Leutz)
 */

class VhostManager
{
    protected $_Config = null;

    /**
     * Return the config
     * @return \QUI\Config
     */
    protected function _getConfig()
    {
        if ( !file_exists( CMS_DIR .'/etc/vhosts.ini' ) ) {
            file_put_contents( CMS_DIR .'/etc/vhosts.ini' , '' );
        }

        $this->_Config = new \QUI\Config( CMS_DIR .'/etc/vhosts.ini' );

        return $this->_Config;
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


    public function addVhost()
    {

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
}