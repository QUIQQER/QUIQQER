<?php

/**
 * This file contains the QUI_Rights_Manager
 */

/**
 * Rights management
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.rights
 */

class QUI_Rights_Manager
{
    const TABLE = 'permissions';

    /**
     * internal right cache
     * @var array
     */
    protected $_cache = array();

    /**
     * constructor
     * load the available rights
     */
    public function __construct()
    {
        try
        {
            $result = \QUI::getDataBase()->fetch(array(
                'from' => \QUI::getDBTableName( self::TABLE )
            ));

            foreach ( $result as $entry ) {
                $this->_cache[ $entry['name'] ] = $entry;
            }
        } catch ( QException $Exception )
        {

        }
    }

    /**
     * Return the area, if the area is an allowed area
     *
     * @param String $area - area string, global, user, groups, site, project, media
     * @return String
     */
    static function parseArea($area)
    {
        switch ( $area )
        {
            case 'global':
            case 'user':
            case 'groups':
            case 'site':
            case 'project':
            case 'media':
                return $area;
            break;
        }

        return '';
    }

    /**
     * Return the corresponding area of a php class
     *
     * @param String $cls
     * @return String
     */
    static function classToArea($cls)
    {
        switch ( $cls )
        {
            case 'Users_User':
                return 'user';
            break;

            case 'Groups_Group':
                return 'groups';
            break;

            case 'Projects_Site':
            case 'Projects_Site_Edit':
            case 'Projects_Site_OnlyDB':
                return 'site';
            break;

            case 'Projects_Project':
                return 'project';
            break;

            // @todo media file classes
            case 'Projects_Media':
                return 'media';
            break;
        }

        return '__null__';
    }

    /**
     * Return the type if the type is an allowed permission type
     *
     * @param String $type - bool, string, int, group, groups, user, users, users_and_groups
     * @return String
     */
    static function parseType($type)
    {
        switch ( $type )
        {
            case 'bool':
            case 'string':
            case 'int':
            case 'array':
            case 'group':
            case 'groups':
            case 'user':
            case 'users':
            case 'users_and_groups':
                return $type;
            break;
        }

        return 'bool';
    }

    /**
     * Rechte Setup, legt alle Felder für die Rechte an
     */
    static function setup()
    {
        $DBTable = \QUI::getDataBase()->Table();
        $table   = \QUI::getDBTableName( self::TABLE );

        $table2users    = $table .'2users';
        $table2groups   = $table .'2groups';
        $table2sites    = $table .'2sites';
        $table2projects = $table .'2projects';
        $table2media    = $table .'2media';

        // Haupttabelle anlegen
        $DBTable->appendFields( $table, array(
            'name'  => 'varchar(100) NOT NULL',
            'type'  => 'varchar(20)  NOT NULL',
            'area'  => 'varchar(20)  NOT NULL',
            'title' => 'varchar(255) NULL',
            'desc'  => 'text NULL',
            'src'   => 'varchar(200) NULL'
        ));

        $DBTable->setIndex( $table, 'name' );


        $DBTable->appendFields( $table2users, array(
            'user_id'     => 'int(11) NOT NULL',
            'permissions' => 'text'
        ));

        $DBTable->appendFields( $table2groups, array(
            'group_id'    => 'int(11) NOT NULL',
            'permissions' => 'text'
        ));

        $DBTable->appendFields( $table2sites, array(
            'project'    => 'varchar(200) NOT NULL',
            'lang'       => 'varchar(2) NOT NULL',
            'id'         => 'bigint(20)',
            'permission' => 'text',
            'value'      => 'text'
        ));

        $DBTable->appendFields( $table2projects, array(
            'project'    => 'varchar(200) NOT NULL',
            'lang'       => 'varchar(2) NOT NULL',
            'permission' => 'text',
            'value'      => 'text'
        ));

        $DBTable->appendFields( $table2media, array(
            'project'    => 'varchar(200) NOT NULL',
            'lang'       => 'varchar(2)',
            'id'         => 'bigint(20)',
            'permission' => 'text',
            'value'      => 'text'
        ));
    }

    /**
     * Add a permission
     *
     * @param array $params - Permission params
     * 						array(
     * 							name =>
     * 							desc =>
     * 							area =>
     * 							title => translation.var.var
     * 							type =>
     * 							default =>
     * 							src =>
     * 						)
     * @throws QException
     */
    public function addPermission($params)
    {
        $DataBase = \QUI::getDataBase();
        $needles  = array( 'name', 'title', 'desc', 'type', 'area', 'src' );

        foreach ( $needles as $needle )
        {
            if ( !isset( $params[ $needle ] ) ) {
                $params[ $needle ] = '';
            }
        }

        if ( empty( $params['name'] ) ) {
            return;
        }

        // if exist update it
        if ( isset( $this->_cache[ $params['name'] ] ) )
        {
            $DataBase->update(
                \QUI::getDBTableName( self::TABLE ),
                array(
                    'title' => trim( $params['title'] ),
                    'desc'  => trim( $params['desc'] ),
                    'type'  => self::parseType( $params['type'] ),
                    'area'  => self::parseArea( $params['area'] ),
                    'src'   => $params['src']
                ),
                array(
                    'name'  => $params['name']
                )
            );

            return;
        }

        // if not exist, insert it
        $DataBase->insert(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'name'  => $params['name'],
                'title' => trim( $params['title'] ),
                'desc'  => trim( $params['desc'] ),
                'type'  => self::parseType( $params['type'] ),
                'area'  => self::parseArea( $params['area'] ),
                'src'   => $params['src']
            )
        );

        $this->_cache[ $params['name'] ] = $params;
    }

    /**
     * Delete a permission
     *
     * @param unknown_type $permission
     * @throws QException
     */
    public function deletePermission($permission)
    {
        $permissions = $this->getPermissionList();

        if ( !isset( $permissions[ $permission ] ) )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.permission.not.found'
                )
            );
        }

        $params = $permissions[ $permission ];

        if ( $params['src'] != 'user' )
        {
            throw new QException(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.permissions.delete.only.user.permissions'
                )
            );
        }

        \QUI::getDataBase()->delete(
            \QUI::getDBTableName( self::TABLE ),
            array(
                'name' => $permission,
                'src'  => 'user'
            )
        );
    }

      /**
     * Import a permissions.xml
     *
     * @param String $xmlfile - Path to the file
     * @param String $src     - optional, the src from where the rights come from
     * 							eq: system, plugin-name, user
     */
    public function importPermissionsFromXml($xmlfile, $src='')
    {
        $permissions = \Utils_Xml::getPermissionsFromXml( $xmlfile );

        if ( !count( $permissions ) ) {
            return;
        }

        foreach ( $permissions as $permission )
        {
            $permission['src'] = $src;

            $this->addPermission( $permission );
        }
    }

    /**
     * Return all available permissions
     *
     * @param String $area - optional, specified the area of the permissions
     * @return Array
     */
    public function getPermissionList($area=false)
    {
        if ( !$area ) {
            return $this->_cache;
        }

        // if an area is specified
        $result = array();

        foreach ( $this->_cache as $key => $params )
        {
            if ( $params['area'] == $area )
            {
                $result[ $key ] = $params;
                continue;
            }

            if ( empty( $params['area'] ) &&
                 ( $area == 'user' || $area == 'groups' ) )
            {
                $result[ $key ] = $params;
            }
        }

        return $result;
    }

    /**
     * Return the permission data
     *
     * @param String $permission - Name of the permission
     * @return false|array
     */
    public function getPermissionData($permission)
    {
        if ( !isset( $this->_cache[ $permission ] ) ) {
            throw new \QException( 'Permission not found' );
        }

        return $this->_cache[ $permission ];
    }

    /**
     * Return all permissions from a group, user, site, project or media
     *
     * @param {Groups_Group|Users_User|Projects_Site} $Obj
     * @return Array
     */
    public function getPermissions($Obj)
    {
        $area = $this->classToArea( get_class( $Obj ) );

        switch ( $area )
        {
            case 'site':
                return $this->getSitePermissions( $Obj );
            break;
        }

        $permissions = array();

        $data  = $this->_getData( $Obj );
        $_list = $this->getPermissionList( $area );

        foreach ( $_list as $permission => $params ) {
            $permissions[ $permission ] = false;
        }

        if ( !isset( $data[0] ) ) {
            return $permissions;
        }

        $obj_permissions = json_decode( $data[0]['permissions'], true );

        foreach ( $obj_permissions as $obj_permission => $value )
        {
            // parse var type

            $permissions[ $obj_permission ] = $value;
        }

        return $permissions;
    }

    /**
     * Return the permissions from a site
     *
     * @param Projects_Site|Projects_Sites_Edit $Site
     * @return array
     */
    public function getSitePermissions($Site)
    {
        $data  = $this->_getData( $Site );
        $_list = $this->getPermissionList( 'site' );

        $permissions = array();

        foreach ( $_list as $permission => $params ) {
            $permissions[ $permission ] = false;
        }

        foreach ( $data as $permission => $value ) {
            $permissions[ $permission ] = $value;
        }

        return $permissions;
    }

    /**
     * Set the permissions for an object
     *
     * @param Users_User|Groups_Group|
     * 	      Projects_Project|Projects_Site|Projects_Site_Edit $Obj
     * @param Array $permissions - Array of permissions
     *
     * @todo permissions for media
     * @todo permissions for project
     */
    public function setPermissions($Obj, $permissions)
    {
        $cls = get_class( $Obj );

        switch ( $cls )
        {
            case 'Users_User':
            case 'Groups_Group':
            case 'Projects_Project':
            break;

            case 'Projects_Site':
            case 'Projects_Site_Edit':
            case 'Projects_Site_OnlyDB':
                $this->setSitePermissions( $Obj, $permissions );
                return;
            break;

            default:
                throw new \QException(
                    'Cannot set Permissions. Object not allowed'
                );
            break;
        }

        $area  = $this->classToArea( $cls );
        $_data = $this->_getData( $Obj ); // old permissions
        $list  = $this->getPermissionList( $area );

        $data = array();

        if ( isset( $_data[0] ) ) {
            $data = $_data[0];
        }

        foreach ( $list as $permission => $params )
        {
            if ( !isset( $permissions[ $permission ] ) ) {
                continue;
            }

            $data[ $permission ] = $this->_cleanValue(
                $params['type'],
                $permissions[ $permission ]
            );
        }

        $DataBase = \QUI::getDataBase();
        $table    = \QUI::getDBTableName( self::TABLE );

        $table2users    = $table .'2users';
        $table2groups   = $table .'2groups';
        $table2projects = $table .'2projects';
        $table2media    = $table .'2media';

        // areas
        switch ( $area )
        {
            case 'user':

                if ( !isset( $_data[0] ) )
                {
                    $DataBase->insert(
                        $table2users,
                        array( 'user_id' => $Obj->getId() )
                    );
                }

                $DataBase->update(
                    $table2users,
                    array( 'permissions' => json_encode( $data ) ),
                    array( 'user_id'     => $Obj->getId() )
                );
            break;

            case 'groups':

                if ( !isset( $_data[0] ) )
                {
                    $DataBase->insert(
                        $table2groups,
                        array( 'group_id' => $Obj->getId() )
                    );
                }

                $DataBase->update(
                    $table2groups,
                    array( 'permissions' => json_encode( $data ) ),
                    array( 'group_id'    => $Obj->getId() )
                );
            break;

            case 'project':

                if ( !isset( $_data[0] ) )
                {
                    $DataBase->insert(
                        $table2projects,
                        array(
                            'project' => $Obj->getAttribute( 'project' ),
                            'lang'    => $Obj->getAttribute( 'lang' )
                        )
                    );
                }

                $DataBase->update(
                    $table2projects,
                    array( 'permissions' => json_encode( $data ) ),
                    array(
                        'project' => $Obj->getName(),
                        'lang'    => $Obj->getLang()
                    )
                );
            break;

            case 'media':
                $Project = $Obj->getProject();

                if ( !isset( $_data[0] ) )
                {
                    $DataBase->insert(
                        $table2media,
                        array(
                            'project' => $Project->getAttribute( 'project' ),
                            'lang'    => $Project->getAttribute( 'lang' ),
                            'id'      => $Obj->getId()
                        )
                    );

                    return;
                }

                $DataBase->update(
                    $table2media,
                    array( 'permissions' => json_encode( $data ) ),
                    array(
                        'project' => $Project->getAttribute( 'project' ),
                        'lang'    => $Project->getAttribute( 'lang' ),
                        'id'      => $Obj->getId()
                    )
                );
            break;
        }
    }

    /**
     * Set the permissions for a site object
     *
     * @param Projects_Site|Projects_Site_Edit|Projects_Site_OnlyDB $Site
     * @param Array $permissions - Array of permissions
     */
    public function setSitePermissions($Site, $permissions)
    {
        switch ( get_class( $Site ) )
        {
            case 'Projects_Site':
            case 'Projects_Site_Edit':
            case 'Projects_Site_OnlyDB':
            break;

            default:
                return;
        }

        $_data = $this->_getData( $Site );
        $list  = $this->getPermissionList( 'site' );

        // look at permission list and cleanup the values
        foreach ( $list as $permission => $params )
        {
            if ( !isset( $permissions[ $permission ] ) ) {
                continue;
            }

            $data[ $permission ] = $this->_cleanValue(
                $params['type'],
                $permissions[ $permission ]
            );
        }

        // set add permissions
        foreach ( $data as $permission => $value )
        {
            if ( !isset( $_data[ $permission ] ) )
            {
                $this->_addSitePermission( $Site, $permission, $value );
                continue;
            }

            $this->_setSitePermission( $Site, $permission, $value );
        }
    }

    /**
     * Updates the permission entry for the site
     *
     * @param unknown $Site
     * @param String $permission
     * @param String|Integer $value
     */
    protected function _setSitePermission($Site, $permission, $value)
    {
        $Project = $Site->getProject();
        $table   = \QUI::getDBTableName( self::TABLE );

        $table2sites = $table .'2sites';

        \QUI::getDataBase()->update(
            $table2sites,
            array( 'value' => $value ),
            array(
                'project'    => $Project->getName(),
                'lang'       => $Project->getLang(),
                'id'         => $Site->getId(),
                'permission' => $permission
            )
        );
    }

    /**
     * Add a new permission entry for site
     *
     * @param unknown $Site
     * @param String $permission
     * @param String|Integer $value
     */
    protected function _addSitePermission($Site, $permission, $value)
    {
        $Project = $Site->getProject();
        $table   = \QUI::getDBTableName( self::TABLE );

        $table2sites = $table .'2sites';

        \QUI::getDataBase()->insert(
            $table2sites,
            array(
                'project'    => $Project->getName(),
                'lang'       => $Project->getLang(),
                'id'         => $Site->getId(),
                'permission' => $permission,
                'value'      => $value
            )
        );
    }

    /**
     * Return the permissions data of an object
     *
     * @param unknown_type $Obj
     * @return Array
     */
    protected function _getData($Obj)
    {
        $DataBase = \QUI::getDataBase();

        $table = \QUI::getDBTableName( self::TABLE );
        $area  = $this->classToArea( get_class( $Obj ) );


        if ( $area === 'user' )
        {
            return $DataBase->fetch(array(
                'from'  => $table .'2users',
                'where' => array(
                    'user_id' => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        if ( $area === 'groups' )
        {
            return $DataBase->fetch(array(
                'from'  => $table .'2groups',
                'where' => array(
                    'group_id' => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        if ( $area === 'project' )
        {
            return $DataBase->fetch(array(
                'from'  => $table .'2projects',
                'where' => array(
                    'project' => $Obj->getName(),
                    'lang'    => $Obj->getLang()
                ),
                'limit' => 1
            ));
        }

        if ( $area === 'site' )
        {
            $Project = $Obj->getProject();

            $data = $DataBase->fetch(array(
                'from'  => $table .'2sites',
                'where' => array(
                    'project' => $Project->getName(),
                    'lang'    => $Project->getLang(),
                    'id'      => $Obj->getId()
                )
            ));

            $result = array();

            foreach ( $data as $entry ) {
                $result[ $entry['permission'] ] = $entry['value'];
            }

            return $result;
        }

        if ( $area === 'media' )
        {
            $Project = $Obj->getProject();

            return $DataBase->fetch(array(
                'from'  => $table .'2media',
                'where' => array(
                    'project' => $Project->getName(),
                    'lang'    => $Project->getLang(),
                    'id'      => $Obj->getId()
                )
            ));
        }

        return false;
    }

    /**
     * Cleanup the value for the type
     *
     * @param String $type
     * @param String|Integer $val
     *
     * @return String|Integer
     */
    protected function _cleanValue($type, $val)
    {
        switch ( $type )
        {
            case 'int':
                $val = (int)$val;
                break;

            case 'users_and_groups':
                // u1234566775 <- user-id
                // g1234566775 <- group-id
                $val = preg_replace( '/[^0-9,ug]/', '', $val );
                break;

            case 'users':
            case 'groups':
                $val = preg_replace( '/[^0-9,]/', '', $val );
                break;

            case 'user':
            case 'group':
                $val = preg_replace( '/[^0-9]/', '', $val );
                break;

            case 'array':
                $val = \Utils_Security_Orthos::clearArray( $val );
                break;

            case 'string':
                $val = \Utils_Security_Orthos::clearMySQL( $val );
                break;

            default:
                $val = (bool)$val;
                break;
        }

        return $val;
    }


    /**
     * ab hier old
     */

    /**
     * Projekt Rechte bekommen
     *
     * @param Projects_Project $Project
     * @return Array
     */
    /*
    public function getProjectRights(Projects_Project $Project)
    {
        $filename = USR_DIR .'lib/'. $Project->getAttribute('template') .'/rights.xml';

        if ( !file_exists( $filename ) ) {
            return array();
        }

        return QUI_Rights_Parser::getRights(
            QUI_Rights_Parser::parse( $filename )
        );
    }

    /**
     * Gibt die XML Gruppen zurück
     *
     * @param Projects_Project $Project
     * @return Array
     */
    /*
    public function getProjectRightGroups(Projects_Project $Project)
    {
        $filename = USR_DIR .'lib/'. $Project->getAttribute('template') .'/rights.xml';

        if ( !file_exists( $filename ) ) {
            return array();
        }

        return QUI_Rights_Parser::getGroups(
            QUI_Rights_Parser::parse( $filename )
        );
    }
    */
    /**
     * Rechte vom Benutzer bekommen
     * Geht bessert über User->getPermission('right')
     *
     * @deprecated
     *
     * @param User $User
     * @param String $right
     * @param Function || String $ruleset
     *
     * @return unknown_type
     *
     *
       @example
        $result = getUserPermission($User, 'namerobot.myarray', function($params)
        {
            return $params['result'];
        });

       @example
        $right = $User->getPermission($perm, 'max_integer');
     */
    public function getUserPermission($User, $right, $ruleset=false)
    {
        /* @var $User User  */
        $groups  = $User->getGroups();
        $integer = false;

        $_rulesetresult = null;

        foreach ( $groups as $Group ) /* @var $Group Group */
        {
            if ( $ruleset )
            {
                $ruleparams = array(
                    'right'  => $right,
                    'result' => $_rulesetresult,
                    'Group'  => $Group
                );

                if ( is_string( $ruleset ) &&
                     method_exists( 'QUI_Rights_PermissionOrder', $rulese ) )
                {
                    $_rulesetresult = QUI_Rights_PermissionOrder::$ruleset( $ruleparams );
                    continue;
                }

                if ( is_string( $ruleset ) ) {
                    throw new QException( 'Unbekanntes Regelset [getUserPermission]' );
                }

                $_rulesetresult = $ruleset( $ruleparams );
                continue;
            }

            $_right = $Group->hasRight( $right );

            // falls wert bool ist
            if ( $_right === true ) {
                return true;
            }

            // falls integer ist
            if ( is_int( $_right ) )
            {
                if ( is_bool( $integer ) ) {
                    $integer = 0;
                }

                if ( $_right > $integer ) {
                    $integer = $_right;
                }

                continue;
            }

            // falls wert string ist
            if ( $_right ) {
                return $_right;
            }
        }

        if ( $_rulesetresult ) {
            return $_rulesetresult;
        }

        if ( !is_bool( $integer ) ) {
            return $integer;
        }

        return false;
    }

    /**
     * Rechte Array einer Gruppe aus den Attributen erstellen
     * Wird zum Beispiel zum Speichern einer Gruppe verwendet
     *
     * @param Groups_Group $Group
     * @return Array
     */
    public function getRightParamsFromGroup(Groups_Group $Group)
    {
        $result = array();
        $rights = QUI::getDataBase()->fetch(array(
            'select' => 'name,type',
            'from'   => self::TABLE
        ));

        foreach ($rights as $right)
        {
            if ($Group->existsRight($right['name']) === false) {
                continue;
            }

            $val = $Group->hasRight($right['name']);

            // bool, string, int, group, array
            switch ($right['type'])
            {
                case 'int':
                    $val = (int)$val;
                break;

                case 'groups':
                    // kommasepariert und zahlen
                    $val = preg_replace('/[^0-9,]/', '', $val);
                break;

                case 'array':
                    $val = Utils_Security_Orthos::clearArray($val);
                break;

                case 'string':
                    $val = Utils_Security_Orthos::clearMySQL( $val );
                break;

                default:
                    $val = (bool)$val;
                break;
            }

            $result[ $right['name'] ] = $val;
        }

        return $result;
    }
}


?>