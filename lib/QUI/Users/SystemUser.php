<?php

/**
 * This file contains \QUI\Users\SystemUser
 */

namespace QUI\Users;

/**
 * the system user
 * Can change things but can not in the admin and can query any ajax functions
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package com.pcsg.qui.users
 */

class SystemUser extends \QUI\Users\Nobody implements \QUI\Interfaces\Users\User
{
    /**
     * construtcor
     */
    public function __construct()
    {
        $this->setAttribute('username', 'system');
    }

    /**
     * (non-PHPdoc)
     *
     * @see \QUI\Users\Nobody::getId()
     */
    public function getId()
    {
        return 5;
    }
}
