<?php

/**
 * This file contains QUI_Desktop
 */

/**
 * Desktop Manager
 *
 * Save and manage the desktops for the users
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Desktop extends \QUI\QDOM
{
    /**
     * Konstruktor
     *
     * @param Array $params
     */
    public function __construct($params)
    {
        self::setAttributes( $params );
    }

    /**
     * Return the Desktop-ID
     *
     * @return Integer
     */
    public function getId()
    {
        return (int)$this->getAttribute( 'id' );
    }

    /**
     * Return the Widgets  from the Desktop
     *
     * @return Array
     */
    public function getWidgetList()
    {
        return json_decode( $this->getAttribute( 'widgets' ), true );
    }
}
