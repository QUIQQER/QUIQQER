<?php

/**
 * This file contains QUI\Users\Auth\Helper
 */

namespace QUI\Users\Auth;

use QUI;
use QUI\Interfaces\Users\User;
use QUI\Permissions\Exception;
use QUI\Users\AuthenticatorInterface;

use function is_object;
use function str_replace;

/**
 * Class Helper
 * Some helper methods, for better authenticator handling
 */
class Helper
{
    /**
     * has the user the permission to user the authenticator
     *
     * @param User|null $User - User
     * @param string|AuthenticatorInterface $authenticator - Name of the authenticator class
     * @return bool
     */
    public static function hasUserPermissionToUseAuthenticator(
        QUI\Interfaces\Users\User|null $User,
        AuthenticatorInterface|string $authenticator
    ): bool {
        if (!QUI::getUsers()->isUser($User)) {
            return false;
        }

        try {
            self::checkUserPermissionToUseAuthenticator($User, $authenticator);
            return true;
        } catch (QUI\Permissions\Exception) {
        }

        return false;
    }

    /**
     * Check if the user has the permission to user the authenticator
     *
     * @param User|null $User - User
     * @param string|AuthenticatorInterface $authenticator - Name of the authenticator class
     * @throws Exception
     */
    public static function checkUserPermissionToUseAuthenticator(
        User|null $User,
        AuthenticatorInterface|string $authenticator
    ): void {
        if (!QUI::getUsers()->isUser($User)) {
            throw new QUI\Permissions\Exception(
                QUI::getLocale()->get('quiqqer/core', 'exception.no.permission'),
                403
            );
        }

        if (is_object($authenticator)) {
            $authenticator = $authenticator::class;
        }

        $permission = self::parseAuthenticatorToPermission($authenticator);

        QUI\Permissions\Permission::checkPermission(
            $permission,
            $User
        );
    }

    /**
     * Return the authenticator class name as a permission name
     *
     * @param string|AuthenticatorInterface $authenticator - Name of the authenticator class
     * @param string $permission - optional, wanted permission, eq: permissionName
     * @return string
     */
    public static function parseAuthenticatorToPermission(
        AuthenticatorInterface|string $authenticator,
        string $permission = ''
    ): string {
        if (is_object($authenticator)) {
            $authenticator = $authenticator::class;
        }

        if (empty($permission)) {
            return 'quiqqer.auth.' . str_replace('\\', '', $authenticator);
        }

        return 'quiqqer.auth.' . str_replace('\\', '', $authenticator) . '.' . $permission;
    }
}
