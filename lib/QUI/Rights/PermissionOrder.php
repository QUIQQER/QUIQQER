<?php

/**
 * This file contains the \QUI\Rights\PermissionOrder
 */

namespace QUI\Rights;

use QUI\Groups\Group;

/**
 * Allgemeine Permission Sotierungs Handling Methoden
 *
 * @example $User->getPermission($perm, 'max_integer');
 * @example $User->getPermission($perm, 'min_integer');
 *
 * @author www.pcsg.de (Henning Leutz)
 *
 */
class PermissionOrder
{
    /**
     * Gibt den Maximalen Integer Rechte Wert zurück
     *
     * @param String $permission - permission name
     * @param Array $groups - List of groups
     * @return Integer
     */
    static function max_integer($permission, $groups)
    {
        $result = null;

        /* @var $Group Group */
        foreach ( $groups as $Group )
        {
            if ( $Group->hasPermission( $permission ) === false ) {
                continue;
            }

            if ( is_null( $result ) || (int)$Group->hasPermission( $permission ) > $result ) {
                $result = (int)$Group->hasPermission( $permission );
            }
        }

        // default
        if ( is_null( $result ) )
        {
            $Manager  = \QUI::getPermissionManager();
            $permData = $Manager->getPermissionData( $permission );

            if ( isset( $permData['defaultvalue'] ) && !empty( $permData['defaultvalue'] ) ) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * Gibt den Minimalen Integer Rechte Wert zurück
     *
     * @param String $permission - permission name
     * @param Array $groups - List of groups
     * @return Integer
     */
    static function min_integer($permission, $groups)
    {
        $result = null;

        /* @var $Group Group */
        foreach ( $groups as $Group )
        {
            if ( $Group->hasPermission( $permission ) === false ) {
                continue;
            }

            if ( is_null( $result ) || (int)$Group->hasPermission( $permission ) < $result ) {
                $result = (int)$Group->hasPermission( $permission );
            }
        }

        // default
        if ( is_null( $result ) )
        {
            $Manager  = \QUI::getPermissionManager();
            $permData = $Manager->getPermissionData( $permission );

            if ( isset( $permData['defaultvalue'] ) && !empty( $permData['defaultvalue'] ) ) {
                return $permData['defaultvalue'];
            }
        }

        return $result;
    }

    /**
     * Prüft die Rechte und gibt das Recht welches Geltung hat zurück
     *
     * @param String $permission - permission name
     * @param Array $groups - List of groups
     * @return boolean|Ambigous <boolean, string>|Ambigous <boolean, number, string>
     */
    static function permission($permission, $groups)
    {
        $result = false;

        /* @var $Group Group */
        foreach ( $groups as $Group )
        {
            $right = $Group->hasPermission( $permission );

            // falls wert bool ist
            if ( $right === true ) {
                return true;
            }

            // falls integer ist
            if ( is_int( $right ) )
            {
                if ( is_bool( $result ) ) {
                    $result = 0;
                }

                if ( $right > $result ) {
                    $result = $right;
                }

                continue;
            }

            // falls wert string ist
            if ( $right ) {
                return $right;
            }
        }

        return $result;
    }
}
