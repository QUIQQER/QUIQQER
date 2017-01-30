<?php

/**
 * This file contains \QUI\Users\AuthInterface
 */

namespace QUI\Users;

/**
 * Interface for external authentification
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package \QUI\Interfaces\Users
 * @licence For copyright and license information, please view the /README.md
 */

interface AuthInterface
{
    /**
     * @param string|array|integer $user - name of the user, or user id
     */
    public function __construct($user = '');

    /**
     * Authenticate the user
     *
     * @param string|array|integer $authParams
     *
     * @throws \QUI\Exception
     */
    public function auth($authParams);

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     */
    public function getUser();

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId();

    /**
     * @return \QUI\Control
     */
    public static function getLoginControl();

    /**
     * @return \QUI\Control
     */
    public static function getRegisterControl();

    /**
     * @return \QUI\Control
     */
    public static function getPasswordResetControl();
}
