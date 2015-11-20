<?php

/**
 * This file contains \QUI\Interfaces\Users\Auth
 */

namespace QUI\Interfaces\Users;

/**
 * Interface for external authentification
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package \QUI\Interfaces\Users
 * @licence For copyright and license information, please view the /README.md
 */

interface Auth
{
    /**
     * @param string $username - name of the user
     */
    public function __construct($username = '');

    /**
     * Authenticate the user
     *
     * @param string $password
     *
     * @return boolean
     *
     * @throws \QUI\Exception
     */
    public function auth($password);

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId();
}
