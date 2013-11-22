<?php

/**
 * This file contains \QUI\Users\User
 */

namespace QUI\Users;

/**
 * A user
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class User implements \QUI\Interfaces\Users\User
{
    /**
     * Project extention
     * @var UserExtend
     */
    public $Extend = null;

    /**
     * The groups in which the user is
     * @var array|\QUI\Groups\Group
     */
    public $Group = array();

    /**
     * User locale object
     * @var \QUI\Locale
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
    protected $_lang = null;

    /**
     * Active status
     * @var Integer
     */
    protected $_active = 0;

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
     * @var \QUI\Users\Users
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
    protected $_extra = array();

    /**
     * user plugins
     * @var Array
     */
    protected $_plugins = array();

    /**
     * User adresses
     * @var Array
     */
    protected $_adress_list = array();

    /**
     * Session id file
     * @var String
     */
    protected $_id_sessid_file = '';

    /**
     * contructor
     *
     * @param Integer $id - ID of the user
     * @param \QUI\Users\Users $Users - the user manager
     * @throws \QUI\Exception
     */
    public function __construct($id, \QUI\Users\Users $Users)
    {
        $id = (int)$id;

        if ( !$id || $id <= 10 )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.wrong.uid'
                ),
                404
            );
        }

        $Groups = \QUI::getGroups();

        $this->_Users = $Users;

        $data = \QUI::getDB()->select(array(
            'from'  => \QUI\Users\Users::Table(),
            'where' => array(
                'id' => (int)$id
            ),
            'limit' => '1'
        ));

        if ( !isset( $data[0] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale(
                    'quiqqer/system',
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
            } catch ( \QUI\Exception $e )
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
        $Plugins = \QUI::getPlugins();
        $plugins = $Plugins->get();

        foreach ( $plugins as $Plugin ) {
            $Plugin->onUserLoad( $this );
        }
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getPermission()
     *
     * @param String $right
     * @param array $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return Bool
     */
    public function getPermission($right, $ruleset=false)
    {
        //@todo Benutzer muss erster prüfen ob bei ihm das recht seperat gesetzt ist

        return \QUI::getRights()->getUserPermission( $this, $right, $ruleset );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getType()
     */
    public function getType()
    {
        return get_class( $this );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getExtra()
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
     * @see \QUI\Interfaces\Users\User::setExtra()
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
     * @see \QUI\Interfaces\Users\User::loadExtra()
     *
     * @param \QUI\Projects\Project $Project
     * @todo für projekte wieder realiseren, vorerst ausgeschaltet
     */
    public function loadExtra(\QUI\Projects\Project $Project)
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
     * @see \QUI\Interfaces\Users\User::getId()
     */
    public function getId()
    {
        return $this->_id ? $this->_id : false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getName()
     */
    public function getName()
    {
        return $this->_name ? $this->_name : false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getLang()
     */
    public function getLang()
    {
        if ( !is_null( $this->_lang ) ) {
            return $this->_lang;
        }

        $lang  = \QUI::getLocale()->getCurrent();
        $langs = \QUI::availableLanguages();

        if ( $this->getAttribute( 'lang' ) ) {
            $lang = $this->getAttribute( 'lang' );
        }

        if ( in_array( $lang, $langs ) ) {
            $this->_lang = $lang;
        }

        // falls null, dann vom Projekt
        if ( !$this->_lang )
        {
            try
            {
                $this->_lang = \QUI\Projects\Manager::get()->getAttribute( 'lang' );
            } catch ( \QUI\Exception $Exception )
            {

            }
        }

        // wird noch gebraucht?
        if ( !$this->_lang ) {
            $this->_lang = \QUI::getLocale()->getCurrent();
        }

        return $this->_lang;
    }

    /**
     * (non-PHPdoc)
     * @see iUser::getLocale()
     */
    public function getLocale()
    {
        if ( $this->Locale ) {
            return $this->Locale;
        }

        $this->Locale = new \QUI\Locale();
        $this->Locale->setCurrent( $this->getLang() );

        return $this->Locale;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getStatus()
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
     * @see \QUI\Interfaces\Users\User::setGroups()
     *
     * @param array|String $groups
     */
    public function setGroups($groups)
    {
        if (empty($groups)) {
            return;
        }

        $Groups = \QUI::getGroups();

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

                } catch (\QUI\Exception $e)
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
            } catch (\QUI\Exception $e)
            {

            }
        }
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getGroups()
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
     * @param \QUI\Groups\Group|Integer $Group
     */
    public function removeGroup($Group)
    {
        $Groups = \QUI::getGroups();

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
        if ($gid == \QUI::conf('globals', 'root')) {
            return; // bad fix, mal provisorisch
        }

        $Groups = \QUI::getGroups();
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
     * @see \QUI\Interfaces\Users\User::setAttribute()
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

        switch ( $key )
        {
            case "su":
                $this->_su = (int)$value;
            break;

            case "username":
            case "name":
                // Falls der Name geändert wird muss geprüft werden das es diesen nicht schon gibt
                \QUI\Users\Users::checkUsernameSigns($value);

                if ($this->_name != $value &&
                    $this->_Users->existsUsername($value))
                {
                    throw new \QUI\Exception('Name existiert bereits');
                }

                $this->_name = $value;
            break;

            case "usergroup":
                $this->setGroups($value);
            break;

            case "expire":
                $time = strtotime( $value );

                if ( $time > 0 ) {
                    $this->_settings[ $key ] = date( 'Y-m-d H:i:s', $time );
                }
            break;

            default:
                $this->_settings[$key] = $value;
            break;
        }
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getAttribute()
     *
     * @param String $var
     * @return String|Ineteger|array
     */
    public function getAttribute($var)
    {
        if ( isset( $this->_settings[ $var ] ) )
        {
            if ( $var == 'avatar' ) {
                return URL_DIR .'media/users/'. $this->_settings[ $var ];
            }

            return $this->_settings[ $var ];
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

        $params['usergroup'] = $this->getGroups( false );
        $params['username']  = $this->getName();

        return $params;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::getAvatar()
     *
     * @param Bool $url - get the avatar with the complete url string
     * @return String
     */
    public function getAvatar($url=false)
    {
        if ( isset( $this->_settings["avatar"] ) )
        {
            if ( $url == true ) {
                return URL_DIR .'media/users/'. $this->_settings["avatar"];
            }

            return $this->_settings["avatar"];
        }

        return false;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::logout()
     */
    public function logout()
    {
        if ( !$this->getId() ) {
            return;
        }

        // Wenn der Benutzer dieser hier ist
        $Users    = \QUI::getUsers();
        $SessUser = $Users->getUserBySession();

        if ( $SessUser->getId() == $this->getId() )
        {
            //session_unset();
            //session_destroy();
            $Session = \QUI::getSession();
            $Session->destroy();
        }

        $sessid = '';

        if ( file_exists( $this->_id_sessid_file ) ) {
            $sessid = file_get_contents($this->_id_sessid_file);
        }

        // Session File löschen
        if ( file_exists( VAR_DIR .'sessions/sess_'. $sessid ) ) {
            unlink( VAR_DIR .'sessions/sess_'. $sessid );
        }

        // ID File löschen
        if ( file_exists( $this->_id_sessid_file ) ) {
            unlink( $this->_id_sessid_file );
        }
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::setPassword()
     *
     * @param String $new - new password
     */
    public function setPassword($new)
    {
        $this->_checkRights();

        if ( empty( $new ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.empty.password'
                )
            );
        }

        $newpass         = \QUI\Users\Users::genHash( $new );
        $this->_password = $newpass;

        \QUI::getDB()->updateData(
            \QUI\Users\Users::Table(),
            array( 'password' => $newpass ),
            array( 'id'       => $this->getId() )
        );

        \QUI::getMessagesHandler()->addSuccess(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'message.password.save.success'
            )
        );
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::checkPassword()
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
     * @see \QUI\Interfaces\Users\User::activate()
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
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.wrong.code'
                )
            );
        }

        if ( $this->_password == '' )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.activasion.no.password'
                )
            );
        }

        $res = \QUI::getDB()->updateData(
            \QUI\Users\Users::Table(),
            array( 'active' => 1 ),
            array( 'id'     => $this->getId() )
        );

        $this->_active = true;

        return $res;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::deactivate()
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
        $projects = \QUI\Projects\Manager::getProjects(true);

        foreach ( $projects as $Project )
        {
            try
            {
                $Extend = $this->loadExtra( $Project) ;

                if ( method_exists( $Extend, 'onDeactivate' ) ) {
                    $Extend->onDelete();
                }

            } catch ( \QUI\Exception $e )
            {

            }
        }

        \QUI::getDB()->updateData(
            \QUI\Users\Users::Table(),
            array('active' => 0),
            array('id'     => $this->getId())
        );

        $this->_active = false;
        $this->logout();

        return true;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::disable()
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
        $projects = \QUI\Projects\Manager::getProjects( true );

        foreach ( $projects as $Project )
        {
            try
            {
                $Extend = $this->loadExtra($Project);

                if ( method_exists( $Extend, 'onDisable' ) ) {
                    $Extend->onDelete();
                }

            } catch ( \QUI\Exception $e )
            {

            }
        }

        \QUI::getDB()->updateData(
            \QUI\Users\Users::Table(),
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
     * @see \QUI\Interfaces\Users\User::save()
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

            if ( \QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax( $value ) ) {
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

            if ( \QUI\Utils\Security\Orthos::checkMySqlDatetimeSyntax( $value ) ) {
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
        $Plugins = \QUI::getPlugins();
        $plugins = $Plugins->get();

        foreach ( $plugins as $Plugin ) {
            $Plugin->onUserSave( $this );
        }

        return \QUI::getDB()->updateData(
            \QUI\Users\Users::Table(),
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
     * @see \QUI\Interfaces\Users\User::isSU()
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
     * @see \QUI\Interfaces\Users\User::isAdmin()
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
     * @see \QUI\Interfaces\Users\User::isDeleted()
     */
    public function isDeleted()
    {
        return $this->_deleted;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isActive()
     */
    public function isActive()
    {
        return $this->_active;
    }

    /**
     * (non-PHPdoc)
     * @see \QUI\Interfaces\Users\User::isOnline()
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
     * @see \QUI\Interfaces\Users\User::delete()
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
        $projects = \QUI\Projects\Manager::getProjects( true );

        foreach ( $projects as $Project )
        {
            try
            {
                $Extend = $this->loadExtra($Project);

                if ( method_exists( $Extend, 'onDelete' ) ) {
                    $Extend->onDelete();
                }

            } catch ( \QUI\Exception $e )
            {

            }
        }

        \QUI::getDB()->deleteData(
            \QUI\Users\Users::Table(),
            array('id' => $this->getId())
        );

        $this->logout();

        return true;
    }

    /**
     * Checks the edit rights of a user
     *
     * @return true
     * @throws \QUI\Exceptions
     */
    protected function _checkRights()
    {
        $User = false;

        $Users = \QUI::getUsers();
        $SUser = $Users->getUserBySession();

        if ( $User && $User->getType() == 'QUI\\Users\\SystemUser' ) {
            return true;
        }

        if ( $SUser->isSU() ) {
            return true;
        }

        if ( $SUser->getId() == $this->getId() ) {
            return true;
        }

        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.lib.user.no.edit.rights'
            )
        );
    }


    /**
     * Add a address to the user
     *
     * @param Array $params
     * @return \QUI\Users\Adress
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
                    \QUI\Utils\Security\Orthos::clearArray( $params[ $needle ] )
                );

                continue;
            }

            $_params[ $needle ] = \QUI\Utils\Security\Orthos::clear(
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

        $Statement = \QUI::getDataBase()->insert(
            \QUI\Users\Users::TableAdress(),
            $_params
        );

        return $this->getAdress(
            \QUI::getDataBase()->getPDO()->lastInsertId()
        );
    }

    /**
     * Returns all adresses from the user
     *
     * @return Array
     */
    public function getAdressList()
    {
        $result = \QUI::getDB()->select(array(
            'from'   => \QUI\Users\Users::TableAdress(),
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
     * @return \QUI\Users\Adress
     */
    public function getAdress($id)
    {
        $id = (int)$id;

        if ( isset($this->_adress_list[ $id ] ) ) {
            return $this->_adress_list[ $id ];
        }

        $this->_adress_list[ $id ] = new \QUI\Users\Adress( $this, $id );

        return $this->_adress_list[ $id ];
    }

    /**
     * return the standard adress from the user
     *
     * @return \QUI\Users\Adress|false
     */
    public function getStandardAdress()
    {
        if ( $this->getAttribute( 'adress' ) === false ) {
            return false;
        }

        return $this->getAdress( $this->getAttribute( 'adress' ) );
    }
}
