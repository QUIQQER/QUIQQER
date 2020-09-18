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
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface Trash
{
    /**
     * Return the trash list
     *
     * @param array $params - \QUI\Utils\Grid params
     *
     * @return array
     */
    public function getList($params = array());
}
