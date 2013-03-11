<?php

/**
 * This file contains Groups_Group
 */

/**
 * A group
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.groups
 */
class Groups_Group extends QDOM
{
	/**
	 * Settings of the group
	 * @var array
	 */
	protected $_settings;

	/**
	 * The group root id
	 * @var Integer
	 */
	protected $_rootid;

	/**
	 * internal right cache
	 * @var array
	 */
	protected $_rights = array();

	/**
	 * internal children id cache
	 * @var array
	 */
	protected $_childrenids = null;

	/**
	 * internal parentid cache
	 * @var array
	 */
	protected $_parentids = null;

	/**
	 * constructor
	 *
	 * @param Integer $id - Group ID
	 */
	public function __construct($id)
	{
		$this->_rootid = QUI::conf('globals','root');
		parent::setAttribute('id', (int)$id);

		try
		{
		    // falls cache vorhanden ist
		    $cache = System_Cache_Manager::get('pcsg.groups-'. $this->getId());

		    $this->_parentids = $cache['parentids'];
    		$this->_rights    = $cache['rights'];

    		if (is_array($cache['attributes']))
    		{
        		foreach ($cache['attributes'] as $key => $value) {
        			$this->setAttribute($key, $value);
        		}
    		}

    		if (!empty($cache)) {
    		    return;
    		}

		} catch (System_Cache_Exception $e)
		{

		}

		$result = QUI::getDataBase()->fetch(array(
			'from'  => Groups_Groups::Table(),
			'where' => array(
				'id' => $this->getId()
			),
			'limit' => '1'
		));

		if (!isset($result[0])) {
			throw new QException('Group doesnt exist', 404);
		}

		foreach ($result[0] as $key => $value) {
			$this->setAttribute($key, $value);
		}

		// rechte setzen
		if ($this->getAttribute('rights')) {
            $this->_rights = json_decode($this->getAttribute('rights'), true);
		}

		$this->_createCache();
	}

	/**
	 * Deletes the group and sub-groups
	 *
	 * @todo alle Beziehungen in den Seiten müssen neu gesetzt werden
	 * @return Bool
	 * @throws QException
	 */
	public function delete()
	{
		// Rootgruppe kann nicht gelöscht werden
		if ((int)QUI::conf('globals','root') === $this->getId()) {
			throw new QException('Die Root Gruppe kann nicht gelöscht werden');
		}

		// Rekursiv die Kinder bekommen
		$children = $this->getChildrenIds(true);

		// Kinder löschen
		foreach ($children as $child)
		{
		    QUI::getDataBase()->exec(array(
		        'delete' => true,
		        'from'  => Groups_Groups::Table(),
                'where' => array(
                	'id' => $child
		        )
		    ));
		}

		// Sich selbst löschen
		QUI::getDataBase()->exec(array(
		        'delete' => true,
		        'from'  => Groups_Groups::Table(),
                'where' => array(
                	'id' => $this->getId()
		        )
		    ));

        System_Cache_Manager::clear('pcsg.groups-'. $this->getId());
	}

	/**
	 * set a group attribute
	 * ID cannot be set
	 *
	 * @param String $key - Attribute name
	 * @param String|Bool|Integer|array $value  - value
	 *
	 * @return Bool
	 */
	public function setAttribute($key, $value)
	{
		if ($key == 'id') {
			return false;
		}

		return parent::setAttribute($key, $value);
	}

	/**
	 * Returns the Group-ID
	 *
	 * @return Integer
	 */
	public function getId()
	{
		return $this->getAttribute('id');
	}

	/**
	 * saves the group
	 * All attributes are set in the database
	 */
	public function save()
	{
	    $this->_rights = QUI::getRights()->getRightParamsFromGroup($this);

		// Felder bekommen
		QUI::getDataBase()->update(
			Groups_Groups::Table(),
    		array(
    		    'name'    => $this->getAttribute('name'),
        		'toolbar' => $this->getAttribute('toolbar'),
        		'admin'   => $this->_rootid == $this->getId() ? 1 : (int)$this->getAttribute('admin'),
    		    'rights'  => json_encode( $this->_rights )
    		),
 			array('id' => $this->getId())
		);

		$this->_createCache();
	}

	/**
	 * Activate the group
	 */
	public function activate()
	{
		QUI::getDataBase()->update(
			Groups_Groups::Table(),
			array('active' => 1),
			array('id'     => $this->getId())
		);

		$this->setAttribute('active', 1);
        $this->_createCache();
	}

	/**
	 * deactivate the group
	 */
	public function deactivate()
	{
		QUI::getDataBase()->update(
			Groups_Groups::Table(),
			array('active' => 0),
			array('id'     => $this->getId())
		);

		$this->setAttribute('active', 0);
		$this->_createCache();
	}

	/**
	 * Is the group active?
	 * @return Bool
	 */
	public function isActive()
	{
        return $this->getAttribute('active') ? true : false;
	}

	/**
	 * Has the group the right?
	 *
	 * @param String $right
	 * @return Bool|String
	 */
	public function hasRight($right)
	{
	    if (isset($this->_rights[$right])) {
            return $this->_rights[$right];
        }

        return false;
	}

	/**
	 * return all rights
	 *
	 * @return Array
	 */
	public function getRights()
	{
	    return $this->_rights;
	}

    /**
     * Exist the right in the group?
     *
     * @param String $right
     * @return Bool
     */
	public function existsRight($right)
	{
	    if ($this->existsAttribute($right)) {
	        return true;
	    }

	    if (isset($this->_rights[$right])) {
            return true;
        }

        return false;
	}

    /**
     * set a right to the group
     *
     * @param Array $rights
     * @throws QException
     */
	public function setRights($rights=array())
	{
        $User = QUI::getUserBySession();

        if (!$User->isSU()) {
            throw new QException('Sie dürfe keine Gruppen bearbeiten');
        }

        foreach ($rights as $k => $v) {
            $this->_rights[$k] = $v;
        }
	}

	/**
	 * return the users from the group
	 *
	 * @param Array $params - SQL Params
	 * @return Array
	 */
	public function getUsers($params=array())
	{
		$params['from']  = Users_Users::Table();
		$params['where'] = array(
		    'usergroup' => array(
		    	'type'  => '%LIKE%',
		        'value' => ','. $this->getId() .','
            )
		);

		return QUI::getDataBase()->fetch($params);
	}

	/**
	 * search a user by name
	 *
	 * @param String $username
	 *
	 * @return Users_User
	 * @throws QException
	 */
	public function getUserByName($username)
	{
		$result = QUI::getDataBase()->fetch(array(
			'select' => 'id',
			'from' 	 => Users_Users::Table(),
			'where'  => 'username = \''. Utils_Security_Orthos::clearMySQL($username)
		                .'\' AND usergroup LIKE \'%,'. $this->getId() .',%\'',
		    'limit'  => '1'
		));

		if (!isset($result[0])) {
			throw new QException('User not found', 404);
		}

		return QUI::getUsers()->get($result[0]['id']);
	}

	/**
	 * returns the user count
	 *
	 * @param Array $params - SQL Params
	 * @return Integer
	 */
	public function countUser($params=array())
	{
		$_params = array(
			'count' => array(
				'select' => 'id',
				'as'     => 'count'
			),
			'from' 	=> Users_Users::Table(),
			'where' => array(
			    'usergroup' => array(
			        'type'  => 'LIKE',
			        'value' => ",'. $this->getId() .',"
			    )
			)
		);

		if (isset($params['order'])) {
			$_params['order'] = $params['order'];
		}

		if (isset($params['limit'])) {
			$_params['limit'] = $params['limit'];
		}

		$result = QUI::getDataBase()->fetch($_params);

		if (isset($result[0]) &&
			isset($result[0]['count']))
		{
			return $result[0]['count'];
		}

		return 0;
	}

	/**
	 * Checks if the ID is from a parent group
	 *
	 * @param Integer $id - ID from parent
	 * @param Bool $recursiv - checks recursive or not
	 *
	 * @return Bool
	 */
	public function isParent($id, $recursiv=false)
	{
		if ($recursiv)
		{
			if (in_array($id, $this->_parentids)) {
				return true;
			}

			return false;
		}

		if ($this->getParent() == $id) {
			return true;
		}

		return false;
	}

	/**
	 * return the parent group
	 *
	 * @param Bool $obj - Parent Objekt (true) oder Parent-ID (false) -> (optional = true)
	 * @return Object|Integer|false
	 * @throws QException
	 */
	public function getParent($obj=true)
	{
		$ids = $this->getParentIds();

		if (!isset($ids[0])) {
			return false;
		}

		if ($obj == true) {
			return QUI::getGroups()->get($ids[0]);
		}

		return $ids[0];
	}

	/**
	 * Get all parent ids
	 *
	 * @return array
	 */
	public function getParentIds()
	{
	    if ($this->_parentids) {
            return $this->_parentids;
	    }

		$this->_parentids = array();

		$result = QUI::getDataBase()->fetch(array(
			'select' => 'id, parent',
			'from'   => Groups_Groups::Table(),
			'where'  => array(
				'id' => $this->getId()
			),
			'limit' => 1
		));

		$this->_parentids[] = $result[0]['parent'];
		$this->_getParentIds($result[0]['parent']);

		return $this->_parentids;
	}

	/**
	 * Helper method for getparents
	 *
	 * @param Integer $id
	 * @ignore
	 */
	private function _getParentIds($id)
	{
		$result = QUI::getDataBase()->fetch(array(
			'select' => 'id, parent',
			'from'   => Groups_Groups::Table(),
			'where'  => array(
				'id' => (int)$id
			),
			'limit' => 1
		));

		if (!isset($result[0]) ||
			!isset($result[0]['parent']))
		{
			return;
		}

		$this->_parentids[] = $result[0]['parent'];
		$this->_getParentIds($result[0]['parent']);
	}

	/**
	 * Have the group subgroups?
	 * @return Integer
	 */
	public function hasChildren()
	{
        return count($this->getChildren());
	}

	/**
	 * Returns the sub groups
	 *
	 * @param Array $params - Where Parameter
	 * @return Array
	 */
	public function getChildren($params=array())
	{
		$ids      = $this->getChildrenIds(false, $params);
		$children = array();
		$Groups   = QUI::getGroups();

		foreach ($ids as $id)
		{
			try
			{
				$Child      = $Groups->get($id);
				$children[] = array_merge(
				    $Child->getAttributes(),
				    array('hasChildren' => $Child->hasChildren())
				);

			} catch (QException $e)
			{
				// nothing
			}
		}

		return $children;
	}

	/**
	 * return the subgroup ids
	 *
	 * @param Bool $recursiv - recursiv true / false
	 * @param $params - SQL Params (limit, order)
	 *
	 * @return Array
	 */
	public function getChildrenIds($recursiv=false, $params=array())
	{
	    if ($this->_childrenids) {
            return $this->_childrenids;
	    }


		$this->_childrenids = array();

		$_params = array(
			'select' => 'id',
			'from' 	 => Groups_Groups::Table(),
			'where'  => array(
				'parent'=> $this->getId()
			)
		);

		if (isset($params['order'])) {
			$_params['order'] = $params['order'];
		}

		if (isset($params['limit'])) {
			$_params['limit'] = $params['limit'];
		}

		$result = QUI::getDataBase()->fetch($_params);

		if (!isset($result) ||
			!isset($result[0]))
		{
			return $this->_childrenids;
		}

		foreach ($result as $entry)
		{
			if (isset($entry['id']))
			{
				$this->_childrenids[] = $entry['id'];

				if ($recursiv == true) {
					$this->_getChildrenIds($entry['id']);
				}
			}
		}

		return $this->_childrenids;
	}

	/**
	 * Helper method for the recursiveness
	 *
	 * @param Integer $id
	 */
	private function _getChildrenIds($id)
	{
		$result = QUI::getDataBase()->fetch(array(
			'select' => 'id',
			'from'   => Groups_Groups::Table(),
			'where'  => array(
				'parent' => $id
			)
		));

		foreach ($result as $entry)
		{
			if (isset($entry['id']))
			{
				$this->_childrenids[] = $entry['id'];
				$this->_getChildrenIds($entry['id']);
			}
		}
	}

	/**
	 * Create a subgroup
	 *
	 * @param String $name - name of the subgroup
	 * @return Groups_Group
	 */
	public function createChild($name)
	{
		$create = true;

		while ($create)
		{
			srand(microtime()*1000000);
	  		$newid = rand(1, 1000000000);

	  		$result = QUI::getDataBase()->fetch(array(
				'select' => 'id',
	  			'from'   => Groups_Groups::Table(),
				'where'  => array(
					'id' => $newid
	  			)
			));

			if (!isset($result[0]) || !$result[0]['id']) {
				$create = false;
			}
		}

		$Statement = QUI::getDataBase()->insert(
			Groups_Groups::Table(),
			array(
				'id'     => $newid,
				'name'   => $name,
				'parent' => $this->getId(),
				'admin'  => 0,
				'active' => 0
			)
		);

		return QUI::getGroups()->get( $newid );
	}

	/**
	 * creates the group cache
	 * @ignore
	 */
	private function _createCache()
	{
		// Cache aufbauen
		System_Cache_Manager::set('pcsg.groups-'. $this->getId(), array(
            'parentids'  => $this->getParentIds(true),
    		'attributes' => $this->getAllAttributes(),
    		'rights'     => $this->_rights
		));
	}
}

?>