<?php

/**
 * This file contains QUI\Users\Auth\Helper
 */
namespace QUI\Users\Auth;

use QUI\Users\AuthInterface;

/**
 * Class Helper
 * Some helper methods, for better authenticator handling
 *
 * @package QUI\Users\Auth
 */
class Helper
{
    /**
     * Return the authenticator class name as a permission name
     *
     * @param string|AuthInterface $authenticator - Name of the authenticator class
     * @param string $permission - optional, wanted permission, eq: canUse
     * @return string
     */
    public static function parseAuthenticatorToPermission($authenticator, $permission = 'canUse')
    {
        if (is_object($authenticator)) {
            $authenticator = get_class($authenticator);
        }

        return 'quiqqer.auth.' . str_replace('\\', '', $authenticator) . '.' . $permission;
    }
}