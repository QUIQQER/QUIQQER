<?php

/**
 * This file contains the QUI_Rights_PermissionOrder
 */

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
class QUI_Rights_PermissionOrder
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

        if ($Group->hasRight($right) === false) {
            return $res;
        }

        if ((int)$Group->hasRight($right) > $res) {
            return (int)$Group->hasRight($right);
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

        if ($Group->hasRight($right) === false) {
            return $res;
        }

        if ((int)$Group->hasRight($right) < $res) {
            return (int)$Group->hasRight($right);
        }

        return $res;
    }
}

?>