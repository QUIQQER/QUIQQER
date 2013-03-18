<?php

/**
 * This file contains QUI_Rights_Permission
 */

/**
 * Provides methods for quick rights checking
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.rights
 */

class QUI_Rights_Permission
{
    /**
     * Prüft den Benutzer auf SuperUser
     *
     * @param User $User - optional
     * @return Bool
     */
    static function isSU($User=false)
    {
        if ( $User === false ) {
            $User = QUI::getUserBySession();
        }

        // old
        if ( $User->isSU() ) {
            return true;
        }

        try
        {
            return self::checkPermission( 'quiqqer.su', $User );
        } catch ( QException $Exception)
        {

        }

        return false;
    }

    /**
     * Checks, if the user is an admin user
     *
     * @param User $User - optional
     * @return Bool
     */
    static function isAdmin($User=false)
    {
        if ( $User === false ) {
            $User = QUI::getUserBySession();
        }

        try
        {
            return self::checkPermission( 'quiqqer.admin', $User );
        } catch ( QException $Exception )
        {

        }

        // old
        if ( $User->isAdmin() ) {
            return true;
        }

        return false;
    }

    /**
     * Prüft ob der Benutzer das Recht besitzt
     *
     * @param String $perm
     * @param Users_User $User
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

        throw new QException(
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
     * @param unknown_type $User
     */
    static function checkSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getRights();
        $permissions = $Manager->getPermissions( $Site );

        if ( isset( $permissions[ $perm ] ) ) {
            return $permissions[ $perm ];
        }

        return false;
    }

    /**
     * Prüft ob der Benutzer auch ein Benutzer ist
     *
     * @param unknown_type $User
     * @throws QException
     */
    static function checkUser($User=false)
    {
        if ( $User === false ) {
            $User = QUI::getUserBySession();
        }

        if ( get_class( $User ) !== 'Users_User' )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }
    }

    /**
     * Prüft ob der Benutzer ein SuperUser ist
     *
     * @param unknown_type $User
     * @throws QException
     */
    static function checkSU($User=false)
    {
        if ( $User === false ) {
            $User = QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isSU() )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403
            );
        }
    }

    /**
     * Prüft ob der Benutzer in den Adminbereich darf
     *
     * @param unknown_type $User
     * @throws QException
     */
    static function checkAdminUser($User=false)
    {
        if ( $User === false ) {
            $User = QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isAdmin( $User ) )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.no.permission'
                ),
                403
            );
        }
    }

    /**
     * Checks if the permission is set
     *
     * @param String $perm
     * @param Users_User|false $User
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
     * @param Users_User $User
     */
    static function existsSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getRights();
        $permissions = $Manager->getPermissions( $Site );

        return isset( $permissions[ $perm ] ) ? true : false;
    }
}

?>