<?php

/**
 * This file contains QUI_Rights_Permission
 */

/**
 * Provides methods for quick rights checking
 *
 * all methods with check throws Exceptions
 * all methods with is or has return the permission value
 *     it makes a check and capture the exceptions
 *
 * all methods with get return the permission entries
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.rights
 */

class QUI_Rights_Permission
{
    /**
     * Checks, if the user is an admin user
     *
     * @param Users_User|false $User - optional
     * @return Bool
     */
    static function isAdmin($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        try
        {
            return self::checkPermission( 'quiqqer.admin', $User );
        } catch ( \QException $Exception )
        {

        }

        // old
        if ( $User->isAdmin() ) {
            return true;
        }

        return false;
    }

    /**
     * Prüft den Benutzer auf SuperUser
     *
     * @param Users_User|false $User - optional
     * @return Bool
     */
    static function isSU($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        // old
        if ( $User->isSU() ) {
            return true;
        }

        try
        {
            return self::checkPermission( 'quiqqer.su', $User );
        } catch ( \QException $Exception)
        {

        }

        return false;
    }

    /**
     * has the User the permission
     *
     * @param String $perm
     * @param Users_User|false $User
     *
     * @return Ambigous <false, string, permission, unknown, boolean>|boolean
     */
    static function hasPermission($perm, $User=false)
    {
        try
        {
            return self::checkPermission( $perm, $User );
        } catch ( \QException $Exception )
        {

        }

        return false;
    }

    /**
     * has the User the permission at the site?
     *
     * @param String $perm
     * @param Projects_Site $Site
     * @param Users_User|false $User - optional
     *
     * @return Ambigous <false, string, permission, unknown, boolean>|boolean
     */
    static function hasSitePermission($perm, $Site, $User=false)
    {
        try
        {
            return self::checkSitePermission($perm, $Site, $User);
        } catch ( \QException $Exception )
        {

        }

        return false;
    }

    /**
     * Prüft ob der Benutzer in den Adminbereich darf
     *
     * @param Users_User|false $User - optional
     * @throws QException
     */
    static function checkAdminUser($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isAdmin( $User ) )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403
            );
        }
    }

    /**
     * Prüft ob der Benutzer das Recht besitzt
     *
     * @param String $perm
     * @param Users_User|false $User - optional
     *
     * @return false|string|permission
     *
     * @throws QException
     */
    static function checkPermission($perm, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        $Manager     = \QUI::getRights();
        $permissions = $Manager->getPermissions( $User );

        // first check user permission
        if ( isset( $permissions[ $perm ] ) &&
             !empty( $permissions[ $perm ] ) )
        {
            return $permissions[ $perm ];
        }

        $groups = $User->getGroups();

        foreach ( $groups as $Group )
        {
            $permissions = $Manager->getPermissions( $Group );

            // @todo we need a check
            if ( isset( $permissions[ $perm ] ) &&
                 !empty( $permissions[ $perm ] ) )
            {
                return $permissions[ $perm ];
            }
        }

        throw new \QException(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.no.permission'
            ),
            403
        );
    }

    /**
     * Checks if the User have the permission of the Site
     *
     * @param String $perm
     * @param unknown_type $Site
     * @param Users_User|false $User - optional
     *
     * @throws QException
     */
    static function checkSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );
        $_found      = array();

        if ( !isset( $permissions[ $perm ] ) ) {
            return true;
        }

        if ( empty( $permissions[ $perm ] ) ) {
            return true;
        }

        // what type
        $perm_data  = $Manager->getPermissionData( $perm );
        $perm_value = $permissions[ $perm ];

        $check = false;

        switch ( $perm_data['type'] )
        {
            case 'bool':
                if ( (bool)$perm_value ) {
                    $check = true;
                }
            break;

            case 'group':
                (int)$perm_value;

            break;

            case 'user':
                if ( (int)$perm_value == $User->getId() ) {
                    $check = true;
                }
            break;

            case 'users':
                $uids = explode( ',', $perm_value );

                foreach ( $uids as $uid )
                {
                    if ( (int)$uid == $User->getId() ) {
                        $check = true;
                    }
                }
            break;

            case 'groups':
            case 'users_and_groups':

                // groups ids from the user
                $group_ids = $User->getGroups( false );
                $group_ids = explode( ',', $group_ids );

                $user_group_ids = array();

                foreach ( $group_ids as $gid ) {
                    $user_group_ids[ $gid ] = true;
                }

                $ids = explode( ',', $perm_value );

                foreach ( $ids as $id )
                {
                    $real_id = $id;
                    $type    = 'g';

                    if ( strpos( $id, 'g' ) !== false ||
                         strpos( $id, 'u' ) !== false )
                    {
                        $real_id = (int)substr( $id, 1 );
                        $type    = substr( $id, 0, 1 );
                    }

                    switch ( $type )
                    {
                        case 'u':
                            if ( $real_id == $User->getId() ) {
                                $check = true;
                            }
                        break;

                        case 'g':
                            if ( isset( $user_group_ids[ $real_id ] ) ) {
                                $check = true;
                            }
                        break;
                    }
                }
            break;
        }

        if ( $check ) {
            return true;
        }

        throw new \QException(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.no.permission'
            ),
            403
        );
    }

    /**
     * Prüft ob der Benutzer ein SuperUser ist
     *
     * @param Users_User|false $User - optional
     * @throws QException
     */
    static function checkSU($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isSU() )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403
            );
        }
    }
    /**
     * Prüft ob der Benutzer auch ein Benutzer ist
     *
     * @param Users_User|false $User - optional
     * @throws QException
     */
    static function checkUser($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        if ( get_class( $User ) !== 'Users_User' )
        {
            throw new \QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }
    }

    /**
     * Return the Site Permission
     *
     * @param Projects_Site|Projects_Site_Edit $Site
     * @param String $perm
     *
     * @return unknown_type|boolean
     */
    static function getSitePermission($Site, $perm)
    {
        $Manager     = \QUI::getRights();
        $permissions = $Manager->getSitePermissions( $Site );

        return isset( $permissions[ $perm ] ) ? $permissions[ $perm ] : false;
    }

    /**
     * Checks if the permission is set
     *
     * @param String $perm
     * @param Users_User|false $User - optional
     *
     * @return Bool
     */
    static function existsPermission($perm, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getRights();
        $permissions = $Manager->getPermissions( $User );

        // first check user permission
        if ( isset( $permissions[ $perm ] ) ) {
            return true;
        }

        $groups = $User->getGroups();

        foreach ( $groups as $Group )
        {
            $permissions = $Manager->getPermissions( $Group );

            if ( isset( $permissions[ $perm ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Checks if the permission exists in the Site
     *
     * @param String $perm
     * @param Projects_Site|Projects_Site_Edit $Site
     * @param Users_User|false $User - optional
     */
    static function existsSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getRights();
        $permissions = $Manager->getSitePermissions( $Site );

        return isset( $permissions[ $perm ] ) ? true : false;
    }
}

?>