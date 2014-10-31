<?php

/**
 * This file contains \QUI\Rights\Permission
 */

namespace QUI\Rights;

use \QUI\System\Log;
use \QUI\Projects\Project;
use \QUI\Users\User;
use \QUI\Groups\Group;

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

class Permission
{
    /**
     * Checks, if the user is an admin user
     *
     * @param \QUI\Users\User|false $User - optional
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

        } catch ( \QUI\Exception $Exception )
        {

        }

        return false;
    }

    /**
     * Prüft den Benutzer auf SuperUser
     *
     * @param \QUI\Users\User|false $User - optional
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

        } catch ( \QUI\Exception $Exception)
        {

        }

        return false;
    }

    /**
     * has the User the permission
     *
     * @param String $perm
     * @param \QUI\Users\User|false $User
     *
     * @return Ambigous <false, string, permission, unknown, boolean>|boolean
     */
    static function hasPermission($perm, $User=false)
    {
        try
        {
            return self::checkPermission( $perm, $User );

        } catch ( \QUI\Exception $Exception )
        {

        }

        return false;
    }

    /**
     * Prüft ob der Benutzer in den Adminbereich darf
     *
     * @param \QUI\Users\User|false $User - optional
     * @throws \QUI\Exception
     */
    static function checkAdminUser($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isAdmin( $User ) )
        {
            throw new \QUI\Exception(
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
     * @param \QUI\Users\User|false $User - optional
     *
     * @return false|string|permission
     *
     * @throws \QUI\Exception
     */
    static function checkPermission($perm, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getPermissions( $User );


        // first check user permission
        if ( isset( $permissions[ $perm ] ) && !empty( $permissions[ $perm ] ) ) {
            return $permissions[ $perm ];
        }

        $groups = $User->getGroups();

        foreach ( $groups as $Group )
        {
            $permissions = $Manager->getPermissions( $Group );

            // @todo we need a check
            if ( isset( $permissions[ $perm ] ) && !empty( $permissions[ $perm ] ) ) {
                return $permissions[ $perm ];
            }
        }

        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.no.permission'
            ),
            403
        );
    }

    /**
     * Check the permission with a given permission list
     *
     * @param array $permissions - list of permissions
     * @param String $perm
     * @param  $User
     *
     * @throws \QUI\Exception
     *
     * @return boolean
     */
    static function checkPermissionList($permissions, $perm, $User=false)
    {
        if ( !isset( $permissions[ $perm ] ) ) {
            return true;
        }

        if ( empty( $permissions[ $perm ] ) ) {
            return true;
        }

        // what type
        $Manager    = \QUI::getPermissionManager();
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

        throw new \QUI\Exception(
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
     * @param \QUI\Users\User|false $User - optional
     * @throws \QUI\Exception
     */
    static function checkSU($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        self::checkUser( $User );

        if ( !self::isSU() )
        {
            throw new \QUI\Exception(
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
     * @param \QUI\Users\User|false $User - optional
     * @throws \QUI\Exception
     */
    static function checkUser($User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        if ( get_class( $User ) !== 'QUI\\Users\\User' )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permission.session.expired'
                ),
                401
            );
        }
    }

    /**
     * Checks if the permission is set
     *
     * @param String $perm
     * @param \QUI\Users\User|false $User - optional
     *
     * @return Bool
     */
    static function existsPermission($perm, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getPermissionManager();
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
     * Sites
     */

    /**
     * Add an user to the permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param String $permission - name of the permission
     */
    static function addUserToSitePermission(User $User, $Site, $permission)
    {
        if ( \QUI\Projects\Site\Utils::isSiteObject( $Site ) === false ) {
            return array();
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission( 'quiqqer.projects.site.edit' );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $user     = 'u'. $User->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions
        if ( isset( $flip[ $user ] ) ) {
            return;
        }

        $permList[] = $user;

        $Manager->setSitePermissions(
            $Site,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Add a group to the permission
     *
     * @param \QUI\Groups\Group $Group
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param String $permission - name of the permission
     */
    static function addGroupToSitePermission(Group $Group, $Site, $permission)
    {
        if ( \QUI\Projects\Site\Utils::isSiteObject( $Site ) === false ) {
            return array();
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission( 'quiqqer.projects.site.edit' );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $group    = 'g'. $Group->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions
        if ( isset( $flip[ $group ] ) ) {
            return;
        }

        $permList[] = $group;


        $Manager->setSitePermissions(
            $Site,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Checks if the User have the permission of the Site
     *
     * @param String $perm
     * @param unknown_type $Site
     * @param \QUI\Users\User|false $User - optional
     *
     * @throws \QUI\Exception
     */
    static function checkSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        if ( $User->isSU() ) {
            return true;
        }


        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        return self::checkPermissionList( $permissions, $perm, $User );
    }

    /**
     * Checks if the permission exists in the Site
     *
     * @param String $perm
     * @param \QUI\Projects\Site\|\QUI\Projects\Site\Edit $Site
     * @param \QUI\Users\User|false $User - optional
     */
    static function existsSitePermission($perm, $Site, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        return isset( $permissions[ $perm ] ) ? true : false;
    }

    /**
     * Return the Site Permission
     *
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param String $perm
     *
     * @return unknown_type|boolean
     */
    static function getSitePermission($Site, $perm)
    {
        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        return isset( $permissions[ $perm ] ) ? $permissions[ $perm ] : false;
    }

    /**
     * has the User the permission at the site?
     *
     * @param String $perm
     * @param \QUI\Projects\Site $Site
     * @param \QUI\Users\User|false $User - optional
     *
     * @return Ambigous <false, string, permission, unknown, boolean>|boolean
     */
    static function hasSitePermission($perm, $Site, $User=false)
    {
        try
        {
            return self::checkSitePermission($perm, $Site, $User);

        } catch ( \QUI\Exception $Exception )
        {

        }

        return false;
    }

    /**
     * Remove a group from the permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param String $permission
     */
    static function removeGroupFromSitePermission(Group $Group, $Site, $permission)
    {
        if ( \QUI\Projects\Site\Utils::isSiteObject( $Site ) === false ) {
            return array();
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission( 'quiqqer.projects.site.edit' );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $group     = 'g'. $Group->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions, than unset it
        if ( isset( $flip[ $group ] ) ) {
            unset( $flip[ $group ] );
        }

        $permList = array_flip( $flip );


        $Manager->setSitePermissions(
            $Site,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Remove an user from the permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Site|\QUI\Projects\Site\Edit $Site
     * @param String $permission
     */
    static function removeUserFromSitePermission(User $User, $Site, $permission)
    {
        if ( \QUI\Projects\Site\Utils::isSiteObject( $Site ) === false ) {
            return array();
        }

        /* @var $Site \QUI\Projects\Site */
        $Site->checkPermission( 'quiqqer.projects.site.edit' );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getSitePermissions( $Site );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $user     = 'u'. $User->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions, than unset it
        if ( isset( $flip[ $user ] ) ) {
            unset( $flip[ $user ] );
        }

        $permList = array_flip( $flip );


        $Manager->setSitePermissions(
            $Site,
            array( $permission => implode(',', $permList) )
        );
    }


    /**
     * Projects
     */

    /**
     * Add an user to the Project permission
     *
     * @param \QUI\Groups\Group $User
     * @param \QUI\Projects\Project $Project
     * @param String $permission
     */
    static function addGroupToProjectPermission(Group $Group, Project $Project, $permission)
    {
        self::checkProjectPermission( 'quiqqer.projects.edit', $Project );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions( $Project );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $groups   = 'g'. $Group->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions
        if ( isset( $flip[ $groups ] ) ) {
            return;
        }

        $permList[] = $groups;

        $Manager->setProjectPermissions(
            $Project,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Add an user to the Project permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Project $Project
     * @param String $permission - name of the
     */
    static function addUserToProjectPermission(User $User, Project $Project, $permission)
    {
        self::checkProjectPermission( 'quiqqer.projects.edit', $Project );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions( $Project );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $user     = 'u'. $User->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions
        if ( isset( $flip[ $user ] ) ) {
            return;
        }

        $permList[] = $user;

        $Manager->setProjectPermissions(
            $Project,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Checks if the User have the permission of the Project
     *
     * @param String $perm
     * @param \QUI\Projects\Project $Project
     * @param \QUI\Users\User|false $User - optional
     *
     * @throws \QUI\Exception
     */
    static function checkProjectPermission($perm, Project $Project, $User=false)
    {
        if ( $User === false ) {
            $User = \QUI::getUserBySession();
        }

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions( $Project );

        return self::checkPermissionList( $permissions, $perm, $User );
    }

    /**
     * Remove an user from the project permission
     *
     * @param \QUI\Users\User $User
     * @param \QUI\Projects\Project $Project
     * @param String $permission - name of the permission
     */
    static function removeUserFromProjectPermission(User $User, Project $Project, $permission)
    {
        self::checkProjectPermission( 'quiqqer.projects.edit', $Project );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions( $Project );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $user     = 'u'. $User->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions, than unset it
        if ( isset( $flip[ $user ] ) ) {
            unset( $flip[ $user ] );
        }

        $permList = array_flip( $flip );


        $Manager->setProjectPermissions(
            $Project,
            array( $permission => implode(',', $permList) )
        );
    }

    /**
     * Remove a group from the project permission
     *
     * @param \QUI\Groups\Group $Group
     * @param \QUI\Projects\Project $Project
     * @param String $permission - name of the permission
     */
    static function removeGroupFromProjectPermission(Group $Group, Project $Project, $permission)
    {
        self::checkProjectPermission( 'quiqqer.projects.edit', $Project );

        $Manager     = \QUI::getPermissionManager();
        $permissions = $Manager->getProjectPermissions( $Project );

        if ( !isset( $permissions[ $permission ] ) )  {
            return;
        }

        $permList = array();
        $group    = 'g'. $Group->getId();

        if ( !empty( $permissions[ $permission ] ) ) {
            $permList = explode( ',', trim( $permissions[ $permission ], ' ,' ) );
        }

        $flip = array_flip( $permList );

        // user is in the permissions, than unset it
        if ( isset( $flip[ $group ] ) ) {
            unset( $flip[ $group ] );
        }

        $permList = array_flip( $flip );


        $Manager->setProjectPermissions(
            $Project,
            array( $permission => implode(',', $permList) )
        );
    }

}
