<?php

/**
 * This file contains QUI_Dependency
 */

/**
 * Dependency class
 * checks dependencies and if they are given
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui
 *
 * @example
 *
 * e.g.: 0.3.18
 * Major Version (0)
 * Minor Version (3)
 * Patch Version (18)
 *
 * checks:
 * 0.4.0
 * 0.4.x
 * 0.x.x
 *
 * >= 0.4.0
 * > 0.4.0
 * <= 0.4.0
 * < 0.4.0
 */

class QUI_Dependency
{
    /**
     * Check the needle version dependence to the present version
     *
     */
    static function check($needle, $present)
    {

    }

    /**
     * Check the Plugin Dependencies
     *
     * @param QUI_Plugins_Plugin $Plugin
     */
    static function checkPlugin(QUI_Plugins_Plugin $Plugin)
    {

    }
}

?>