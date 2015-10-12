<?php

/**
 * This file contains \QUI\Users\SystemUser
 */

namespace QUI\Users;

use QUI;

/**
 * the system user
 * Can change things but can not in the admin and can query any ajax functions
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 * @package QUI\Users
 */
class SystemUser extends QUI\Users\Nobody implements QUI\Interfaces\Users\User
{
    /**
     * construtcor
     */
    public function __construct()
    {
        parent::__construct();

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

    /**
     * @param bool|true $array
     * @return array
     */
    public function getGroups($array = true)
    {
        $Everyone = new QUI\Groups\Everyone();

        if ($array == true) {
            return array($Everyone);
        }

        return array($Everyone->getId());
    }
}
