<?php

/**
 * This file contains QUI_Desktop_Widget
 */

/**
 * A desktop Widget
 * parent class
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 */

class QUI_Desktop_Widget extends \QUI\QDOM
{
    /**
     * Konstruktor
     */
    public function __construct($attributes=array())
    {
        self::setAttributes( $attributes );
    }

    /**
     * Create the js
     *
     * @return String
     */
    public function toJS()
    {

    }
}
