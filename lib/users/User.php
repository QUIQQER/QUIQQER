<?php

/**
 * This file contains Interface_Users_User
 */

/**
 * A user
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class Users_User implements Interface_Users_User
{
	const TABLE = 'pcsg_users';

	/**
	 * Project extention
	 * @var UserExtend
	 */
	public $Extend = null;

	/**
	 * The groups in which the user is
	 * @var array|Groups_Group
	 */
	public $Group = array();

	/**
	 * User locale object
	 * @var QUI_Locale
	 */
	public $Locale = null;

	/**
	 * User ID
	 * @var Integer
	 */
	protected $_id;

	/**
	 * User groups
	 * @var array
	 */
	protected $_groups;

	/**
	 * Username
	 * @var String
	 */
	protected $_name;

	/**
	 * User lang
	 * @var String
	 */
	protected $_lang    = null;

	/**
	 * Active status
	 * @var Integer
	 */
	protected $_active  = 0;

	/**
	 * Delete status
	 * @var Integer
	 */
	protected $_deleted = 0;

    /**
     * Super user flag
     * @var Bool
     */
	protected $_su = false;

	/**
	 * Admin flag
	 * @var Bool
	 */
	protected $_admin = null;

	/**
	 * Settings
	 * @var array
	 */
	protected $_settings;

	/**
	 * User manager
	 * @var Users_Users
	 */
	protected $_Users;

	/**
	 * Encrypted pass
	 * @var String
	 */
	protected $_password;

	/**
	 * Extra fields
	 * @var Array
	 */
	protected $_extra   = array();

	/**
	 * user plugins
	 * @var Array
	 */
	protected $_plugins = array();

    /**
     * User adresses
     * @var Array
     */
	protected $_adress_list    = array();

	/**
	 * Session id file
	 * @var String
	 */
	protected $_id_sessid_file = '';

	/**
	 * contructor
	 *
	 * @param Integer $id - ID of the user
	 * @param Users_Users $Users - the user manager
	 * @throws QException
	 */
	public function __construct($id, Users_Users $Users)
	{
		$id = (int)$id;

		if ( !$id || $id <= 10 )
		{
			throw new QException(
				QUI::getLocale()->get(
					'system',
					'exception.lib.user.wrong.uid'
				),
			    404
			);
		}

		$Groups = QUI::getGroups();

		$this->_Users = $Users;

		$data = QUI::getDB()->select(array(
			'from'  => self::TABLE,
			'where' => array(
				'id' => (int)$id
			),
			'limit' => '1'
		));

		if ( !isset( $data[0] ) )
		{
			throw new QException(
				QUI::getLocale(
					'system',
					'exception.lib.user.not.found'
				),
				404
			);
		}

		// Eigenschaften setzen
		if ( isset( $data[0]['username'] ) )
		{
			$this->_name = $data[0]['username'];
			unset( $data[0]['username'] );
		}

		if ( isset( $data[0]['id'] ) )
		{
			$this->_id = $data[0]['id'];
			unset( $data[0]['id'] );
		}

		if ( isset( $data[0]['usergroup'] ) )
		{
			try
			{
				$this->setGroups( $data[0]['usergroup'] );
			} catch ( QException $e )
			{
				// nohting
			}

			unset( $data[0]['usergroup'] );
		}

		if ( isset( $data[0]['active'] ) && $data[0]['active'] == 1 ) {
			$this->_active = 1;
		}

		if ( $data[0]['active'] == -1 ) {
			$this->_deleted = 1;
		}

		if ( isset( $data[0]['su'] ) && $data[0]['su'] == 1 ) {
			$this->_su = true;
		}

		if ( isset( $data[0]['password'] ) ) {
			$this->_password = $data[0]['password'];
		}

		foreach ( $data[0] as $key => $value )
		{
		    if ( $key == 'user_agent' )
		    {
		        $this->_settings['user_agent'] = $value;
		        continue;
		    }

			$this->setAttribute( $key, $value );
		}

		if ( $this->getAttribute( 'expire' ) == '0000-00-00 00:00:00' ) {
			$this->setAttribute( 'expire', false );
		}

        // @todo sessions prüfen mit einstellungen
		$this->_id_sessid_file = VAR_DIR .'uid_sess/'. $id;

		// Extras
		if ( $this->getAttribute( 'extra' ) ) {
			$this->_extra = json_decode( $this->getAttribute('extra'), true );
		}

        // Plugins laden
		$Plugins = QUI::getPlugins();
		$plugins = $Plugins->get();

		foreach ( $plugins as $Plugin ) {
    		$Plugin->onUserLoad( $this );
		}
	}

    /**
     * (non-PHPdoc)
     * @see Interface_Users_User::getPermission()
     *
     * @param String $right
     * @param array $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return Bool
     */
	public function getPermission($right, $ruleset=false)
	{
        //@todo Benutzer muss erster prüfen ob bei ihm das recht seperat gesetzt ist

        return QUI::getRights()->getUserPermission( $this, $right, $ruleset );
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getType()
	 */
	public function getType()
	{
		return get_class( $this );
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getExtra()
	 *
	 * @param String $field
	 * @return String|Integer|array
	 */
	public function getExtra( $field )
	{
		if ( isset($this->_extra[ $field] ) ) {
			return $this->_extra[ $field ];
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setExtra()
	 *
	 * @param String $field
	 * @param String|Integer|array $value
	 */
	public function setExtra($field, $value)
	{
		$this->_extra[ $field ] = $value;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::loadExtra()
	 *
	 * @param Projects_Project $Project
	 * @todo für projekte wieder realiseren, vorerst ausgeschaltet
	 */
	public function loadExtra(Projects_Project $Project)
	{
	    return false;

		if ( !file_exists( USR_DIR .'lib/'. $Project->getAttribute('name') .'/User.php' ) ) {
			return false;
		}

		if ( !class_exists('UserExtend') ) {
			require USR_DIR .'lib/'. $Project->getAttribute('name') .'/User.php';
		}

		if ( class_exists('UserExtend') )
		{
			$this->Extend = new UserExtend( $this, $Project );
			return $this->Extend;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getId()
	 */
	public function getId()
	{
		if ($this->_id) {
			return $this->_id;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getName()
	 */
	public function getName()
	{
		if ($this->_name) {
			return $this->_name;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getLang()
	 */
	public function getLang()
	{
		if (!is_null($this->_lang)) {
	        return $this->_lang;
	    }

	    $lang  = QUI::getLocale()->getCurrent();
	    $langs = QUI::availableLanguages();

	    if ($this->getAttribute('lang')) {
            $lang = $this->getAttribute('lang');
	    }

        if (in_array($lang, $langs)) {
            $this->_lang = $lang;
        }

        // falls null, dann vom Projekt
        if (!$this->_lang) {
            $this->_lang = Projects_Manager::get()->getAttribute('lang');
        }

        // wird noch gebraucht?
	    if (!$this->_lang) {
            $this->_lang = QUI::getLocale()->getCurrent();
        }

        return $this->_lang;
	}

    /**
     * (non-PHPdoc)
     * @see iUser::getLocale()
     */
	public function getLocale()
	{
	    if ($this->Locale) {
	        return $this->Locale;
	    }

        $this->Locale = new QUI_Locale();
        $this->Locale->setCurrent($this->getLang());

        return $this->Locale;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getStatus()
	 */
	public function getStatus()
	{
		if ( $this->_active ) {
			return $this->_active;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setGroups()
	 *
	 * @param array|String $groups
	 */
	public function setGroups($groups)
	{
		if (empty($groups)) {
			return;
		}

		$Groups = QUI::getGroups();

		$this->Group   = array();
		$this->_groups = false;

		if (is_array($groups))
		{
			$aTmp = array();

			foreach ($groups as $group)
			{
				$tg = $Groups->get($group);

				if ($tg)
				{
					$this->Group[] = $tg;
					$aTmp[]        = $group;
				}
			}

			$this->_groups = implode($aTmp, ',');

		} elseif (is_string($groups) && strpos($groups,',') !== false)
		{
			$groups = explode(',', $groups);
			$aTmp   = array();

			foreach ($groups as $g)
			{
				if (empty($g)) {
				    continue;
				}

				try
                {
					$this->Group[] = $Groups->get($g);
					$aTmp[] = $g;

                } catch (QException $e)
                {
                    // nothing
                }
			}

			$this->_groups = ','. implode($aTmp, ',') .',';

		} elseif (is_string($groups))
		{
		    try
            {
				$this->Group[] = $Groups->get($groups);
				$this->_groups = ','.$groups.',';
			} catch (QException $e)
			{

			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getGroups()
	 *
	 * @param Bool $array - returns the groups as objects (true) or as an array (false)
	 * @return array
	 */
	public function getGroups($array=true)
	{
		if ( $this->Group && is_array( $this->Group ) )
		{
			if ( $array == true ) {
				return $this->Group;
			}

			return $this->_groups;
		}

		return false;
	}

	/**
	 * Remove a group from the user
	 *
	 * @param Groups_Group|Integer $Group
	 */
	public function removeGroup($Group)
	{
		$Groups = QUI::getGroups();

		if (is_string($Group) || is_int($Group)) {
			$Group = $Groups->get((int)$Group);
		}

		$groups = $this->getGroups(true);
		$new_gr = array();

		if (!is_array($groups)) {
			$groups = array();
		}

		foreach ($groups as $key => $UserGroup)
		{
			if ($UserGroup->getId() != $Group->getId()) {
				$new_gr[] = $UserGroup->getId();
			}
		}

		$this->setGroups($new_gr);
	}

	/**
	 * Add the user to a group
	 *
	 * @param Integer $gid
	 */
	public function addGroup($gid)
	{
		/* @todo Root Gruppe darf nur in Root Gruppe */
		if ($gid == QUI::conf('globals', 'root')) {
			return; // bad fix, mal provisorisch
		}

		$Groups = QUI::getGroups();
		$Group  = $Groups->get($gid);

		$groups = $this->getGroups(true);
		$new_gr = array();
		$_tmp   = array();

		if (!is_array($groups)) {
			$groups = array();
		}

		$groups[] = $Group;


		foreach ($groups as $key => $UserGroup)
		{
			if (isset($_tmp[ $UserGroup->getId() ])) {
				continue;
			}

			$_tmp[ $UserGroup->getId() ] = true;

			$new_gr[] = $UserGroup->getId();
		}

		$this->setGroups($new_gr);
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setAttribute()
	 *
	 * @param String $key
	 * @param String|Integer|Array $value
	 */
	public function setAttribute($key, $value)
	{
		if (!$key ||
		    $key == 'id' ||
		    $key == 'password' ||
		    $key == 'user_agent')
		{
			return;
		}

		switch ($key)
		{
			case "su":
				$this->_su = (int)$value;
			break;

			case "username":
			case "name":
				// Falls der Name geändert wird muss geprüft werden das es diesen nicht schon gibt
				Users_Users::checkUsernameSigns($value);

				if ($this->_name != $value &&
					$this->_Users->existsUsername($value))
				{
					throw new QException('Name existiert bereits');
				}

				$this->_name = $value;
			break;

			case "usergroup":
                $this->setGroups($value);
			break;

			default:
				$this->_settings[$key] = $value;
			break;
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getAttribute()
	 *
	 * @param String $var
	 * @return String|Ineteger|array
	 */
	public function getAttribute($var)
	{
	    if (isset($this->_settings[$var]))
		{
			if ($var == 'avatar') {
				return URL_DIR .'media/users/'. $this->_settings[$var];
			}

			return $this->_settings[$var];
		}

		return false;
	}

	/**
	 * Return all user attributes
	 * @return Array
	 */
	public function getAllAttributes()
	{
		$params = $this->_settings;

		$params['id']       = $this->getId();
		$params['active']   = $this->_active;
		$params['deleted']  = $this->_deleted;
		$params['admin']    = $this->isAdmin();
		$params['avatar']   = $this->getAvatar();
		$params['su']		= $this->isSU();

		$params['usergroup'] = $this->getGroups(false);
		$params['username']  = $this->getName();

		return $params;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getAvatar()
	 *
	 * @param Bool $url - get the avatar with the complete url string
	 * @return String
	 */
	public function getAvatar($url=false)
	{
		if (isset($this->_settings["avatar"]))
		{
			if ($url == true) {
				return URL_DIR .'media/users/'. $this->_settings["avatar"];
			}

			return $this->_settings["avatar"];
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::logout()
	 */
	public function logout()
	{
		if (!$this->getId()) {
			return;
		}

		// Wenn der Benutzer dieser hier ist
		$Users    = QUI::getUsers();
		$SessUser = $Users->getUserBySession();

		if ($SessUser->getId() == $this->getId())
		{
			//session_unset();
			//session_destroy();
			$Session = QUI::getSession();
			$Session->destroy();
		}

		$sessid = '';

		if (file_exists($this->_id_sessid_file)) {
			$sessid = file_get_contents($this->_id_sessid_file);
		}

		// Session File löschen
		if (file_exists(VAR_DIR .'sessions/sess_'. $sessid)) {
			unlink(VAR_DIR .'sessions/sess_'. $sessid);
		}

		// ID File löschen
		if (file_exists($this->_id_sessid_file)) {
			unlink($this->_id_sessid_file);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setPassword()
	 *
	 * @param String $new - new password
	 */
	public function setPassword($new)
	{
		$this->_checkRights();

		if ( empty( $new ) )
		{
			throw new QException(
		        QUI::getLocale()->get(
		        	'system',
		        	'exception.lib.user.empty.password'
		        )
			);
		}

		$newpass         = Users_Users::genHash( $new );
		$this->_password = $newpass;

		return QUI::getDB()->updateData(
			self::TABLE,
			array( 'password' => $newpass ),
			array( 'id'       => $this->getId() )
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::checkPassword()
	 *
	 * @param String $pass 		- Password
	 * @param Bool $encrypted	- is the given password already encrypted?
	 */
	public function checkPassword($pass, $encrypted=false)
	{
		if ( !$encrypted )
		{
			$_pw = $this->_Users->genHash( $pass );
		} else
		{
			$_pw = $pass;
		}

		return $_pw == $this->_password ? true : false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::activate()
	 *
	 * @param String $code - activasion code [optional]
	 */
	public function activate($code=false)
	{
		if ( $code == false ) {
			$this->_checkRights();
		}

		if ( $code && $code != $this->getAttribute( 'activation' ) )
		{
			throw new QException(
			    QUI::getLocale()->get(
			    	'system',
			    	'exception.lib.user.activasion.wrong.code'
			    )
			);
		}

		if ( $this->_password == '' )
		{
			throw new QException(
			    QUI::getLocale()->get(
			    	'system',
			    	'exception.lib.user.activasion.no.password'
			    )
			);
		}

		$res = QUI::getDB()->updateData(
			self::TABLE,
			array( 'active' => 1 ),
			array( 'id'     => $this->getId() )
		);

		$this->_active = true;

		return $res;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::deactivate()
	 */
	public function deactivate()
	{
		$this->_checkRights();

		// Pluginerweiterungen - onDisable Event
		foreach ( $this->_plugins as $Plugin )
		{
			if ( method_exists( $Plugin, 'onDeactivate' ) ) {
				$Plugin->onDisable( $this );
			}
		}

		// Extra von den Projekten
		$projects = Projects_Manager::getProjects(true);

		foreach ( $projects as $Project )
		{
			try
			{
				$Extend = $this->loadExtra( $Project) ;

				if ( method_exists( $Extend, 'onDeactivate' ) ) {
					$Extend->onDelete();
				}

			} catch ( QException $e )
			{

			}
		}

		QUI::getDB()->updateData(
			self::TABLE,
			array('active' => 0),
			array('id'     => $this->getId())
		);

		$this->_active = false;
		$this->logout();

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::disable()
	 */
	public function disable()
	{
		$this->_checkRights();

		// Pluginerweiterungen - onDisable Event
		foreach ( $this->_plugins as $Plugin )
		{
			if ( method_exists( $Plugin, 'onDisable') ) {
				$Plugin->onDisable( $this );
			}
		}

		// Extra von den Projekten
		$projects = Projects_Manager::getProjects( true );

		foreach ( $projects as $Project )
		{
			try
			{
				$Extend = $this->loadExtra($Project);

				if ( method_exists( $Extend, 'onDisable' ) ) {
					$Extend->onDelete();
				}

			} catch ( QException $e )
			{

			}
		}

		QUI::getDB()->updateData(
			self::TABLE,
			array(
				'active'     => -1,
				'password'   => '',
				'usergroup'  => '',
				'firstname'  => '',
				'lastname'   => '',
				'usertitle'  => '',
				'birthday'   => '',
				'email'      => '',
				'su'         => 0,
				'avatar'     => '',
				'extra'      => '',
				'lang'       => '',
				'shortcuts'  => '',
				'activation' => '',
				'expire'     => '0000-00-00 00:00:00'
			),
			array( 'id' => $this->getId() )
		);

		$this->logout();

		return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::save()
	 */
	public function save()
	{
		$this->_checkRights();

		$expire   = '0000-00-00 00:00:00';
		$birthday = '0000-00-00';

		if ( $this->getAttribute( 'expire' ) )
		{
			// Datumsprüfung auf Syntax
			$value = trim( $this->getAttribute( 'expire' ) );

			if ( Utils_Security_Orthos::checkMySqlDatetimeSyntax( $value ) ) {
				$expire = $value;
			}
		}

		if ( $this->getAttribute( 'birthday') )
		{
			// Datumsprüfung auf Syntax
			$value = trim( $this->getAttribute( 'birthday' ) );

			if ( strlen( $value ) == 10 ) {
				$value .= ' 00:00:00';
			}

			if ( Utils_Security_Orthos::checkMySqlDatetimeSyntax( $value ) ) {
				$birthday = substr( $value, 0, 10 );
			}
		}

		// Pluginerweiterungen - onSave Event
		/*
		foreach ($this->_plugins as $Plugin)
		{
			if (method_exists($Plugin, 'onSave')) {
				$Plugin->onSave($this);
			}
		}
		*/
		$Plugins = QUI::getPlugins();
		$plugins = $Plugins->get();

		foreach ( $plugins as $Plugin ) {
            $Plugin->onUserSave( $this );
		}

        return QUI::getDB()->updateData(
			self::TABLE,
			array(
				'username' 	=> $this->getName(),
				'usergroup' => $this->getGroups(false),
				'firstname' => $this->getAttribute( 'firstname' ),
				'lastname' 	=> $this->getAttribute( 'lastname' ),
				'usertitle' => $this->getAttribute( 'usertitle' ),
				'birthday' 	=> $birthday,
				'email' 	=> $this->getAttribute( 'email' ),
				'avatar' 	=> $this->getAvatar(),
				'su'		=> $this->isSU(),
				'extra' 	=> json_encode( $this->_extra ),
				'lang' 	    => $this->getAttribute( 'lang' ),
				'lastedit'  => date( "Y-m-d H:i:s" ),
				'expire'    => $expire,
				'shortcuts' => $this->getAttribute( 'shortcuts' ),
				'adress'    => (int)$this->getAttribute( 'adress' )
			),
			array('id' => $this->getId())
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isSU()
	 */
	public function isSU()
	{
		if ( $this->_su == true ) {
			return true;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isAdmin()
	 */
	public function isAdmin()
	{
		if ( !is_null( $this->_admin ) ) {
			return $this->_admin;
		}

		$this->_admin = false;

		$groups = $this->getGroups();

		if ( !is_array( $groups ) ) {
			return false;
		}

		foreach ( $groups as $Group )
		{
			if ( $Group->getAttribute('admin') )
			{
				$this->_admin = true;
				return true;
			}
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isDeleted()
	 */
	public function isDeleted()
	{
		return $this->_deleted;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isActive()
	 */
	public function isActive()
	{
		return $this->_active;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isOnline()
	 */
	public function isOnline()
	{
		if ( !file_exists( $this->_id_sessid_file ) ) {
			return false;
		}

		$sessid = file_get_contents( $this->_id_sessid_file );

		if ( file_exists( VAR_DIR .'sessions/sess_'. $sessid ) ) {
			return true;
		}

		return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::delete()
	 */
	public function delete()
	{
		// Pluginerweiterungen - onDelete Event
		foreach ( $this->_plugins as $Plugin )
		{
			if ( method_exists( $Plugin, 'onDelete' ) ) {
				$Plugin->onDelete( $this );
			}
		}

		// Extra von den Projekten
		$projects = Projects_Manager::getProjects( true );

		foreach ( $projects as $Project )
		{
			try
			{
				$Extend = $this->loadExtra($Project);

				if ( method_exists( $Extend, 'onDelete' ) ) {
					$Extend->onDelete();
				}

			} catch ( QException $e )
			{

			}
		}

		QUI::getDB()->deleteData(
			self::TABLE,
			array('id' => $this->getId())
		);

		$this->logout();

		return true;
	}

	/**
	 * Checks the edit rights of a user
	 *
	 * @return true
	 * @throws QExceptions
	 */
	protected function _checkRights()
	{
	    $User = false;

		$Users = QUI::getUsers();
		$SUser = $Users->getUserBySession();

		if ( $User && $User->getType() == 'Users_SystemUser' ) {
			return true;
		}

		if ( $SUser->isSU() ) {
			return true;
		}

		if ( $SUser->getId() == $this->getId() ) {
			return true;
		}

		throw new QException(
		    QUI::getLocale()->get('system', 'exception.lib.user.no.edit.rights')
		);
	}


	/**
	 * Add a address to the user
	 *
	 * @param Array $params
	 * @return Users_Adress
	 */
	public function addAdress($params)
	{
		$_params = array();
		$needles = array(
			'salutation', 'firstname', 'lastname',
			'phone', 'mail', 'company',
			'delivery', 'street_no', 'zip', 'city',
			'country'
		);

		foreach ( $needles as $needle )
		{
			if ( !isset( $params[ $needle ] ) )
			{
			    $_params[ $needle ] = '';
			    continue;
			}

			if ( is_array( $params[ $needle ] ) )
			{
                $_params[ $needle ] = json_encode(
                    Utils_Security_Orthos::clearArray( $params[ $needle ] )
                );

                continue;
			}

			$_params[ $needle ] = Utils_Security_Orthos::clear(
			    $params[ $needle ]
	        );
		}

		$tmp_first = $this->getAttribute( 'firstname' );
		$tmp_last  = $this->getAttribute( 'lastname' );

		if ( empty( $tmp_first ) && empty( $tmp_last ) )
		{
		    $this->setAttribute( 'firstname', $_params[ 'firstname' ] );
		    $this->setAttribute( 'lastname', $_params[ 'lastname' ] );
		    $this->save();
		}


		$_params[ 'uid' ] = $this->getId();

		$Statement = QUI::getDataBase()->insert(
		    Users_Users::TBL_ADDR,
		    $_params
		);

		return $this->getAdress(
            QUI::getDataBase()->getPDO()->lastInsertId()
		);
	}

	/**
	 * Returns all adresses from the user
	 *
	 * @return Array
	 */
	public function getAdressList()
	{
		$result = QUI::getDB()->select(array(
			'from'   => Users_Users::TBL_ADDR,
			'select' => 'id',
			'where'  => array(
				'uid' => $this->getId()
			)
		));

		if ( !isset( $result[ 0 ] ) ) {
	        return array();
		}

		$list = array();

		foreach ( $result as $entry )
		{
			$id = (int)$entry[ 'id' ];
			$list[ $id ] = $this->getAdress( $id );
		}

		return $list;
	}

	/**
	 * Get a adress from the user
	 *
	 * @param Integer $id - adress ID
	 * @return Users_Adress
	 */
	public function getAdress($id)
	{
		$id = (int)$id;

		if ( isset($this->_adress_list[ $id ] ) ) {
			return $this->_adress_list[ $id ];
		}

		$this->_adress_list[ $id ] = new Users_Adress( $this, $id );

		return $this->_adress_list[ $id ];
	}

	/**
	 * return the standard adress from the user
	 *
	 * @return Users_Adress|false
	 */
	public function getStandardAdress()
	{
		if ( $this->getAttribute( 'adress' ) === false ) {
			return false;
		}

		return $this->getAdress( $this->getAttribute( 'adress' ) );
	}
}

?>