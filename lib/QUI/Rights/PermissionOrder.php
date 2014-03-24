<?php

/**
 * This file contains the \QUI\Rights\PermissionOrder
 */

namespace QUI\Rights;

/**
 * Allgemeine Permission Sotierungs Handling Methoden
 *
 * @example $User->getPermission($perm, 'max_integer');
 * @example $User->getPermission($perm, 'min_integer');
 *
 * @author www.pcsg,de (Henning Leutz)
 * @package com.pcsg.qui.rights
 *
 */
class PermissionOrder
{
    /**
     * Gibt den Maximalen Integer Rechte Wert zurück
     *
     * @param unknown_type $params
     * @return Integer
     */
    static function max_integer($params)
    {
        $right = $params['right'];
        $res   = (int)$params['result'];
        $Group = $params['Group'];

        if ( $Group->hasPermission( $right ) === false ) {
            return $res;
        }

        if ( (int)$Group->hasPermission( $right ) > $res ) {
            return (int)$Group->hasPermission( $right );
        }

        return $res;
    }

    /**
     * Gibt den Minimalen Integer Rechte Wert zurück
     *
     * @param unknown_type $params
     * @return Integer
     */
    static function min_integer($params)
    {
        $right = $params['right'];
        $res   = (int)$params['result'];
        $Group = $params['Group'];

        if ( $Group->hasPermission( $right ) === false ) {
            return $res;
        }

        if ( (int)$Group->hasPermission( $right ) < $res ) {
            return (int)$Group->hasPermission( $right );
        }

        return $res;
    }
}
