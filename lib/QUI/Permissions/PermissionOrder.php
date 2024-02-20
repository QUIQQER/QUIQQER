<?php

/**
 * This file contains the \QUI\Permissions\PermissionOrder
 */

namespace QUI\Permissions;

use QUI;
use QUI\Exception;
use QUI\Groups\Group;
use QUI\Users\User;

use function is_bool;
use function is_int;

/**
 * Class PermissionOrder
 *
 * The PermissionOrder class provides methods for calculating the maximum and minimum integer values
 * of a specified permission from a list of objects, as well as checking if a permission is granted
 * for any object in the given list.
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 *
 * @example $User->getPermission($perm, 'max_integer');
 * @example $User->getPermission($perm, 'min_integer');
 */
class PermissionOrder
{
    /**
     * Finds the maximum integer value of a specified permission from a list of objects.
     *
     * @param string $permission The permission to check.
     * @param array $list An array of objects to check permissions against.
     *
     * @return int|null The maximum integer value of the permission, or null if no objects have the permission.
     * @throws Exception
     */
    public static function maxInteger(string $permission, array $list): ?int
    {
        $result = null;

        foreach ($list as $Object) {
            if (QUI::getGroups()->isGroup($Object)) {
                /* @var $Object Group */
                $hasPermissionResult = $Object->hasPermission($permission);
            } else {
                /* @var $Object User */
                $hasPermissionResult = $Object->hasPermission($permission);
            }

            if ($hasPermissionResult === false) {
                continue;
            }

            if ($result === null || (int)$hasPermissionResult > $result) {
                $result = (int)$hasPermissionResult;
            }
        }

        // default
        if ($result === null) {
            $Manager = QUI::getPermissionManager();
            $permData = $Manager->getPermissionData($permission);

            if (!empty($permData['defaultvalue'])) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * Calculates the minimum integer result of checking a permission against a list of objects.
     *
     * @param string $permission The permission to check against.
     * @param array $list The list of objects to check the permission against.
     * @return int|null The minimum integer result. If no object has the permission, returns null.
     * @throws Exception
     */
    public static function minInteger(string $permission, array $list): ?int
    {
        $result = null;

        /* @var $Object Group */
        foreach ($list as $Object) {
            $hasPermissionResult = $Object->hasPermission($permission);
            
            if ($hasPermissionResult === false) {
                continue;
            }

            if ($result === null || (int)$hasPermissionResult < $result) {
                $result = (int)$Object->hasPermission($permission);
            }
        }

        // default
        if ($result === null) {
            $Manager = QUI::getPermissionManager();
            $permData = $Manager->getPermissionData($permission);

            if (!empty($permData['defaultvalue'])) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * Checks if a permission is granted for any object in the given list.
     *
     * @param string $permission The permission to check for.
     * @param array $list The list of objects to check against.
     *
     * @return bool|int|string Returns true if the permission is granted by any object,
     *         the highest integer permission value if multiple objects have integer
     *         permissions, or the string permission if granted by any object.
     */
    public static function permission(string $permission, array $list)
    {
        $result = false;

        /* @var $Group Group */
        foreach ($list as $Object) {
            if (QUI::getGroups()->isGroup($Object)) {
                /* @var $Object Group */
                $hasPermissionResult = $Object->hasPermission($permission);
            } else {
                /* @var $Object User */
                $hasPermissionResult = $Object->hasPermission($permission);
            }

            // falls wert boolean ist
            if ($hasPermissionResult === true) {
                return true;
            }

            // falls integer ist
            if (is_int($hasPermissionResult)) {
                if (is_bool($result)) {
                    $result = 0;
                }

                if ($hasPermissionResult > $result) {
                    $result = $hasPermissionResult;
                }

                continue;
            }

            // falls wert string ist
            if ($hasPermissionResult) {
                return $hasPermissionResult;
            }
        }

        return $result;
    }
}
