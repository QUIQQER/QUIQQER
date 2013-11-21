<?php

/**
 * This file contains \QUI\Interfaces\Projects\Trash
 */

namespace QUI\Interfaces\Projects;

/**
 * The trash interface
 *
 * it shows the main methods of a trash
 * a trash are used eq. by projects and the media center
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.projects
 */

interface Trash
{
    /**
     * Return the trash list
     *
     * @param Array $params - \QUI\Utils\Grid params
     * @return Array
     */
    public function getList($params=array());
}
