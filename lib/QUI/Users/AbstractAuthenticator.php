<?php

/**
 * This file contains \QUI\Users\AbstractAuthenticator
 */

namespace QUI\Users;

use QUI;

/**
 * Parent class for external Authenticator
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package \QUI\Interfaces\Users
 * @licence For copyright and license information, please view the /README.md
 */
abstract class AbstractAuthenticator implements QUI\Users\AuthenticatorInterface
{
    /**
     * Return the ID of the user
     *
     * @return integer
     * @throws QUI\Users\Exception
     */
    public function getUserId()
    {
        return $this->getUser()->getId();
    }

    /**
     * Return the login control
     *
     * @return \QUI\Control|null
     */
    public static function getLoginControl()
    {
        return null;
    }

    /**
     * Return the password reset control
     *
     * @return \QUI\Control|null
     */
    public static function getPasswordResetControl()
    {
        return null;
    }

    /**
     * Return the settings control (eq: for administration)
     *
     * @return \QUI\Control|null
     */
    public static function getSettingsControl()
    {
        return null;
    }

    /**
     * @return bool
     */
    public static function isCLICompatible()
    {
        return false;
    }

    /**
     * The CLI Authentication, only if isCLICompatible returns true
     *
     * @param \QUI\System\Console $Console
     */
    public function cliAuthentication(\QUI\System\Console $Console)
    {
        return;
    }
}
