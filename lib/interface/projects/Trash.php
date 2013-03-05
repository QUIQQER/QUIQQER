<?php

/**
 * This file contains Interface_Projects_Trash
 */

/**
 * The trash interface
 *
 * it shows the main methods of a trash
 * a trash are used eq. by projects and the media center
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.projects
 */

interface Interface_Projects_Trash
{
    /**
     * Return the trash list
     *
     * @param Array $params - Utils_Grid params
     * @return Array
     */
    public function getList($params=array());
}

?>