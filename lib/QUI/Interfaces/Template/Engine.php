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
     * @param String $template - path to the template
     * @return String
     */
    public function fetch($template);

    /**
     * Assign a Variable to the engine
     *
     * @param array|String $var
     * @param unknown_type $value - optional
     */
    public function assign($var, $value=false);
}
