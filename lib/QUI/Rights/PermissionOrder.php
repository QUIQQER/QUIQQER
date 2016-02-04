<?php

/**
 * This file contains the \QUI\Rights\PermissionOrder
 */

namespace QUI\Rights;

use QUI;
use QUI\Groups\Group;
use QUI\Users\User;

/**
 * Allgemeine Permission Sotierungs Handling Methoden
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
     * Gibt den Maximalen integer Rechte Wert zurück
     *
     * @param string $permission - permission name
     * @param array $list - List of groups or users
     *
     * @return integer
     */
    public static function maxInteger($permission, $list)
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
            $Manager  = QUI::getPermissionManager();
            $permData = $Manager->getPermissionData($permission);

            if (isset($permData['defaultvalue'])
                && !empty($permData['defaultvalue'])
            ) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * @deprecated
     */
    public static function max_integer($permission, $list)
    {
        self::maxInteger($permission, $list);
    }

    /**
     * Gibt den Minimalen integer Rechte Wert zurück
     *
     * @param string $permission - permission name
     * @param array $list - List of groups or users
     *
     * @return integer
     */
    public static function minInteger($permission, $list)
    {
        $result = null;

        /* @var $Group Group */
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

            if ($result === null || (int)$hasPermissionResult < $result) {
                $result = (int)$Group->hasPermission($permission);
            }
        }

        // default
        if ($result === null) {
            $Manager  = QUI::getPermissionManager();
            $permData = $Manager->getPermissionData($permission);

            if (isset($permData['defaultvalue'])
                && !empty($permData['defaultvalue'])
            ) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * @deprecated
     */
    public static function min_integer($permission, $list)
    {
        return self::minInteger($permission, $list);
    }

    /**
     * Prüft die Rechte und gibt das Recht welches Geltung hat zurück
     *
     * @param string $permission - permission name
     * @param array $list - List of groups or users
     *
     * @return boolean
     */
    public static function permission($permission, $list)
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
