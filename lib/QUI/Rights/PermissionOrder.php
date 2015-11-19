<?php

/**
 * This file contains the \QUI\Rights\PermissionOrder
 */

namespace QUI\Rights;

use QUI;
use QUI\Groups\Group;

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
     * Gibt den Maximalen Integer Rechte Wert zurück
     *
     * @param string $permission - permission name
     * @param array $groups - List of groups
     *
     * @return Integer
     */
    static function max_integer($permission, $groups)
    {
        $result = null;

        /* @var $Group Group */
        foreach ($groups as $Group) {

            if ($Group->hasPermission($permission) === false) {
                continue;
            }

            if (is_null($result)
                || (int)$Group->hasPermission($permission) > $result
            ) {
                $result = (int)$Group->hasPermission($permission);
            }
        }

        // default
        if (is_null($result)) {

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
     * Gibt den Minimalen Integer Rechte Wert zurück
     *
     * @param string $permission - permission name
     * @param array $groups - List of groups
     *
     * @return Integer
     */
    static function min_integer($permission, $groups)
    {
        $result = null;

        /* @var $Group Group */
        foreach ($groups as $Group) {

            if ($Group->hasPermission($permission) === false) {
                continue;
            }

            if (is_null($result)
                || (int)$Group->hasPermission($permission) < $result
            ) {
                $result = (int)$Group->hasPermission($permission);
            }
        }

        // default
        if (is_null($result)) {

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
     * Prüft die Rechte und gibt das Recht welches Geltung hat zurück
     *
     * @param string $permission - permission name
     * @param array $groups - List of groups
     *
     * @return boolean
     */
    static function permission($permission, $groups)
    {
        $result = false;

        /* @var $Group Group */
        foreach ($groups as $Group) {
            $right = $Group->hasPermission($permission);

            // falls wert boolean ist
            if ($right === true) {
                return true;
            }

            // falls integer ist
            if (is_int($right)) {
                if (is_bool($result)) {
                    $result = 0;
                }

                if ($right > $result) {
                    $result = $right;
                }

                continue;
            }

            // falls wert string ist
            if ($right) {
                return $right;
            }
        }

        return $result;
    }
}
