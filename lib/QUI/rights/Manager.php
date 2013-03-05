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
        $result = \QUI::getDataBase()->fetch(array(
            'from' => \QUI::getDBTableName( self::TABLE )
        ));

        foreach ( $result as $entry ) {
            $this->_cache[ $entry['name'] ] = $entry;
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
     * @param String $area - bool, string, int, group, groups, user, users, array
     * @return String
     */
    static function parseType($type)
    {
        switch ( $type )
        {
            case 'bool':
            case 'string':
            case 'int':
            case 'group':
            case 'groups':
            case 'user':
            case 'users':
            case 'array':
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
     * Return all permissions from a group or an user
     *
     * @param {Groups_Group|Users_User} $Obj
     * @return Array
     */
    public function getPermissions($Obj)
    {
        $DataBase    = \QUI::getDataBase();
	    $table       = \QUI::getDBTableName( self::TABLE );
	    $permissions = array();

	    $area  = $this->classToArea( get_class( $Obj ) );
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
     * Set the permissions for an object
     *
     * @param Users_User|Groups_Group|
     * 	      Projects_Project|Projects_Site|Projects_Site_Edit $Obj
     * @param Array $permissions - Array of permissions
     */
    public function setPermissions($Obj, $permissions)
    {
        $cls = get_class( $Obj );

        switch ( $cls )
        {
            case 'Users_User':
            case 'Groups_Group':
            case 'Projects_Project':
            case 'Projects_Site':
            case 'Projects_Site_Edit':
            break;

            default:
                throw new QException(
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

            $val = $permissions[ $permission ];

            switch ( $params['type'] )
            {
                case 'int':
                    $val = (int)$val;
                break;

                case 'users_and_groups':
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

            $data[ $permission ] = $val; // data check
        }

        $DataBase = \QUI::getDataBase();
        $table    = \QUI::getDBTableName( self::TABLE );

	    $table2users    = $table .'2users';
	    $table2groups   = $table .'2groups';
	    $table2sites    = $table .'2sites';
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
                    	'project' => $Obj->getAttribute( 'project' ),
                    	'lang'    => $Obj->getAttribute( 'lang' )
                    )
                );
            break;

            case 'site':
                $Project = $Obj->getProject();

                if ( !isset( $_data[0] ) )
                {
                    $DataBase->insert(
                        $table2sites,
                        array(
                        	'project' => $Project->getAttribute( 'project' ),
                        	'lang'    => $Project->getAttribute( 'lang' ),
                            'id'      => $Obj->getId()
                        )
                    );
                }

                $DataBase->update(
                    $table2sites,
                    array( 'permissions' => json_encode( $data ) ),
                    array(
                    	'project' => $Project->getAttribute( 'project' ),
                    	'lang'    => $Project->getAttribute( 'lang' ),
                        'id'      => $Obj->getId()
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
     * Return the data of an object an by an area
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
                    'project' => $Obj->getAttribute( 'project' ),
            		'lang'    => $Obj->getAttribute( 'lang' )
                ),
                'limit' => 1
            ));
        }

        if ( $area === 'site' )
        {
            $Project = $Obj->getProject();

            return $DataBase->fetch(array(
                'from'  => $table .'2sites',
                'where' => array(
                    'project' => $Project->getAttribute( 'project' ),
            		'lang'    => $Project->getAttribute( 'lang' ),
            		'id'      => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        if ( $area === 'media' )
        {
            $Project = $Obj->getProject();

            return $DataBase->fetch(array(
                'from'  => $table .'2media',
                'where' => array(
                    'project' => $Project->getAttribute( 'project' ),
            		'lang'    => $Project->getAttribute( 'lang' ),
            		'id'      => $Obj->getId()
                ),
                'limit' => 1
            ));
        }

        return false;
    }


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

	/**
	 * Prüft ob der Benutzer ein Recht auf das gewünschte Recht hat
	 *
	 * @param User || Nobody $User
	 * @param unknown_type $Site
	 * @param unknown_type $right
	 *
	 * @return Bool
	 */
	public function hasRights($User, $Site, $right='')
	{
		if (is_object($User) && $User->getType() == 'SystemUser') {
			return true; /* @todo weis noch nicht ob das so toll ist */
		}

		$Groups = QUI::getGroups();

		if ($right == 'view')
		// Ansichtsrecht muss anderst geprüft werden
		{
			$check = $this->getRightFromSite($Site, $right);

			if (!isset($check) || empty($check) || !is_array($check)) {
				return true;
			}
		}

		if (!is_object($User) || !$User->getId()) {
			return false;
		}

		if (empty($right)) {
			return true;
		}

		// Wenn kein Recht gesetzt ist dann durchlassen
		if (!isset($check)) {
			$check = $this->getRightFromSite($Site, $right);
		}

		if (!isset($check) || empty($check) || !is_array($check)) {
			return true;
		}

		$UserGroups = $User->getGroups();
		$childIds   = array();

		for ($i = 0; $i < count($UserGroups); $i++)
		{
			if (isset($UserGroups[$i]) && is_object($UserGroups[$i]))
			{
				$childIds[] = $UserGroups[$i]->getId();
				$cIds       = $UserGroups[$i]->getChildrenIds(true);

				$childIds = array_merge($childIds, $cIds);
			}
		}

		for ($i = 0; $i < count($check); $i++)
		{
			if (in_array($check[$i], $childIds))
			{
				return true;
				break;
			}
		}

		return false;
	}

    /**
     * Rechte vom Benutzer bekommen
     * Geht bessert über User->getPermission('right')
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

		foreach ($groups as $Group) /* @var $Group Group */
		{
            if ($ruleset)
            {
                $ruleparams = array(
                    'right'  => $right,
                    'result' => $_rulesetresult,
                    'Group'  => $Group
                );

                if (is_string($ruleset) && method_exists('QUI_Rights_PermissionOrder', $ruleset))
                {
                    $_rulesetresult = QUI_Rights_PermissionOrder::$ruleset($ruleparams);
                    continue;
                }

                if (is_string($ruleset)) {
                    throw new QException('Unbekanntes Regelset [getUserPermission]');
                }

                $_rulesetresult = $ruleset($ruleparams);
                continue;
            }

            $_right = $Group->hasRight($right);

            // falls wert bool ist
            if ($_right === true) {
                return true;
            }

            // falls integer ist
            if (is_int($_right))
            {
                if (is_bool($integer)) {
                    $integer = 0;
                }

                if ($_right > $integer) {
                    $integer = $_right;
                }

                continue;
            }

            // falls wert string ist
            if ($_right) {
                return $_right;
            }
		}

		if ($_rulesetresult) {
		    return $_rulesetresult;
		}

		if (!is_bool($integer)) {
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

	/**
	 * Gibt die Rechte der Seite zurück
	 *
	 * @param Projects_Site / Projects_Site_Edit $Site
	 * @param String $right - Recht welches gesucht wird
	 * 	(optional - wenn nichts angegeben werden werden alle zurückgegben)
	 * @return Array | String | false
	 */
	public function getRightsFromSite($Site, $right=false)
	{
		return $this->getRightFromSite($Site, $right);
	}

	/**
	 * Rechtegruppen einer bestimmten Seite bekommen
	 *
	 * @param Projects_Site $Site
	 * @param String $right
	 * @param array $array
	 */
	public function getRightFromSite($Site, $right, $array=true)
	{
		if (!is_object($Site) || !$Site->getId()) {
			return false;
		}

		$id      = $Site->getId();
		$Project = $Site->getProject();

		$table = $Project->getAttribute('name') .'_'.
		         $Project->getAttribute('lang') .'_rights';

		if (isset($this->_rightcache[$table]) &&
		    isset($this->_rightcache[$table][$id]))
		{
			$result = $this->_rightcache[$table][$id];
		} else
		{
			$result = QUI::getDataBase()->fetch(array(
				'from'  => $table,
				'where' => array(
					'id' => $id
				),
				'limit' => '1'
			));

			$this->_rightcache[$table][$id] = $result;
		}

		// Falls alle Rechte gewollt werden
		if (isset($result[0]) && $right == false) {
			return $result[0];
		}

		if (!isset($result[0]) || !isset($result[0][$right])) {
			return false;
		}

		$groups = explode(',', $result[0][$right]);
		$g      = array();

		for ($i = 0; $i < count($groups); $i++)
		{
			if (isset($groups[$i]) && $groups[$i] != '') {
				$g[] = $groups[$i];
			}
		}

		return count($g) ? $g : false;
	}

	/**
	 * Setzt Rechte in die DB
	 *
	 * @param Projects_Site|Projects_Site_Edit $Site
	 * @param array $rights
	 */
	public function setRightsFromSite($Site, array $rights)
	{
	    $id      = $Site->getId();
		$Project = $Site->getProject();

		$table = $Project->getAttribute('name') .'_'.
		         $Project->getAttribute('lang') .'_rights';

		$result = QUI::getDataBase()->fetch(array(
			'select' => 'id',
			'from'   => $table,
			'where'  => array(
				'id' => $id
			)
		));

		if (!isset($result[0]))
		{
			QUI::getDataBase()->insert(
				$table,
				array('id' => $id)
			);
		}

		// Felder aussortieren
		$fields = QUI::getDataBase()->Table()->getFields($table);

		foreach ($rights as $key => $value)
		{
			if (in_array($key, $fields) == false ) {
				unset($rights[$key]);
			}
		}

		if (is_array($rights) && !empty($rights))
		{
		    QUI::getDataBase()->exec(array(
                'update' => $table,
		        'set'    => $rights,
		        'where'  => array(
		        	'id' => $id
		        )
		    ));

			if (isset($this->_rightcache[$table]) &&
			    isset($this->_rightcache[$table][$id]))
			{
				unset( $this->_rightcache[$table][$id] );
			}
		}
	}

	/**
	 * Prüft ob die Gruppe $parent ein Parent von $children ist
	 *
	 * @param (GroupId) Integer $parent
	 * @param (GroupId) Integer $children
	 */
	/*
	public function isParent($parent, $children)
	{
		if (is_object($parent)) {
			$parent = $parent->getId();
		}

		if (is_object($children))
		{
			$cache = $children->getGroupCache();

			if (in_array($parent, $cache)) {
				return true;
			}

		} else
		{
			$cache_file = VAR_DIR .'cache/rights/'. $children;

			if (file_exists($cache_file))
			{
				$cache_content = file_get_contents($cache_file);
				$cache         = unserialize($cache_content);

				if (in_array($parent, $cache)) {
					return true;
				}

			} else
			{
				try
				{
					$children = new Group($children);
					$cache    = $children->getGroupCache();

					if (in_array($parent, $cache)) {
						return true;
					}

				} catch (Exception $e)
				{
					// nothing
				}
			}
		}

		return false;
	}
	*/
}


?>