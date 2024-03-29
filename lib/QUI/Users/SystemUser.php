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
     * @see \QUI\Users\Nobody::getUniqueId()
     */
    public function getUniqueId()
    {
        return '5';
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     * @see \QUI\Interfaces\Users\User::getUsername()
     *
     */
    public function getUsername(): string
    {
        return $this->getName();
    }

    /**
     * (non-PHPdoc)
     *
     * @return string
     * @see \QUI\Interfaces\Users\User::getName()
     *
     */
    public function getName(): string
    {
        return $this->getAttribute('username');
    }

    /**
     * @param bool|true $array
     * @return array
     */
    public function getGroups($array = true): array
    {
        $Everyone = new QUI\Groups\Everyone();

        if ($array === true) {
            return [$Everyone];
        }

        return [$Everyone->getId()];
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
