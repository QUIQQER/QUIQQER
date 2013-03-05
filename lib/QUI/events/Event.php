<?php

/**
 * This file contains QUI_Events_Event
 */

/**
 * A QUIQQER Event
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.event
 *
 * @example $Event = new Event(function() {
 * 	// some code here
 * });
 */

class QUI_Events_Event
{
    /**
     * callable function
     * @var function
     */
    protected $_onfire = null;

    /**
     * constructor
     */
    public function __construct()
    {
        $callback = func_get_args();

        // wenn leer
        if (empty($callback)) {
            return;
        }

        // Wenn nicht aufrufbar
        if (!is_callable($callback[0])) {
            return;
        }

        $this->_onfire = $callback;
    }

    /**
     * Execute the event
     *
     * @param Array $params - Übergabeparameter
     */
    public function fire($params=array())
    {
        if (!$this->_onfire) {
            return;
        }

        $this->_onfire($params);
    }
}


?>