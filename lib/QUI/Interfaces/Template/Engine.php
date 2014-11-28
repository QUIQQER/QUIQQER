<?php

/**
 * This file contains \QUI\Interfaces\Template\Engine
 */

namespace QUI\Interfaces\Template;

/**
 * Interface of a template engine
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.template
 */

interface Engine
{
    /**
     * Return the complete template
     *
     * @param string $template - path to the template
     * @return string
     */
    public function fetch($template);

    /**
     * Assign a Variable to the engine
     *
     * @param array|string $var
     * @param mixed $value - optional
     */
    public function assign($var, $value=false);

    /**
     * Extend the html header
     *
     * @param String $str
     * @param Integer $prio
     */
    public function extendHeader($str, $prio=3);
}
