<?php

/**
 * This file contains QUI\Users\Auth\Helper
 */
namespace QUI\Users\Auth;

use QUI;
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
     * @param string $permission - optional, wanted permission, eq: permissionName
     * @return string
     */
    public static function parseAuthenticatorToPermission($authenticator, $permission = '')
    {
        if (is_object($authenticator)) {
            $authenticator = get_class($authenticator);
        }

        if (empty($permission)) {
            return 'quiqqer.auth.' . str_replace('\\', '', $authenticator);
        }

        return 'quiqqer.auth.' . str_replace('\\', '', $authenticator) . '.' . $permission;
    }

    /**
     * Check if the user has the permission to user the authenticator
     *
     * @param QUI\Interfaces\Users\User|null|false $User - User
     * @param string|AuthInterface $authenticator - Name of the authenticator class
     * @throws QUI\Permissions\Exception
     */
    public static function checkUserPermissionToUseAuthenticator($User, $authenticator)
    {
        if (!QUI::getUsers()->isUser($User)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get('quiqqer/system', 'exception.no.permission'),
                403
            );
        }

        if (is_object($authenticator)) {
            $authenticator = get_class($authenticator);
        }

        $permission = self::parseAuthenticatorToPermission($authenticator);

        QUI\Permissions\Permission::checkPermission(
            $permission,
            $User
        );
    }

    /**
     * has the the user the permission to user the authenticator
     *
     * @param QUI\Interfaces\Users\User|null|false $User - User
     * @param string|AuthInterface $authenticator - Name of the authenticator class
     * @return bool
     */
    public static function hasUserPermissionToUseAuthenticator($User, $authenticator)
    {
        if (!QUI::getUsers()->isUser($User)) {
            return false;
        }

        try {
            self::checkUserPermissionToUseAuthenticator($User, $authenticator);
            return true;
        } catch (QUI\Permissions\Exception $Exception) {
        }

        return false;
    }
}
