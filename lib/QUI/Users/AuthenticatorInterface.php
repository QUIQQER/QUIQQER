<?php

/**
 * This file contains \QUI\Users\AuthenticatorInterface
 */

namespace QUI\Users;

/**
 * Interface for external authentification
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
interface AuthenticatorInterface
{
    /**
     * @param string|array|integer $user - name of the user, or user id
     */
    public function __construct($user = '');

    /**
     * Return the login control
     *
     * @return \QUI\Control|null
     */
    public static function getLoginControl();

    /**
     * Return the password reset control
     *
     * @return \QUI\Control|null
     */
    public static function getPasswordResetControl();

    /**
     * Return the settings control (eq: for administration)
     *
     * @return \QUI\Control|null
     */
    public static function getSettingsControl();

    /**
     * @return bool
     */
    public static function isCLICompatible();

    /**
     * Authenticate the user
     *
     * @param string|array|integer $authParams
     *
     * @throws \QUI\Users\Exception
     */
    public function auth($authParams);

    /**
     * Return the user object
     *
     * @return \QUI\Interfaces\Users\User
     */
    public function getUser();

    /**
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getTitle($Locale = null);

    /**
     * @param null|\QUI\Locale $Locale
     * @return string
     */
    public function getDescription($Locale = null);

    /**
     * CLI
     */

    /**
     * Return the quiqqer user id
     *
     * @return integer|boolean
     */
    public function getUserId();

    /**
     * The CLI Authentication, only if isCLICompatible returns true
     *
     * @param \QUI\System\Console $Console
     */
    public function cliAuthentication(\QUI\System\Console $Console);
}
