<?php

/**
 * This file contains \QUI\Users\Manager
 */

namespace QUI\Users;

/**
 * QUIQQER user manager
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class Manager
{
    /**
     * @var PT_Session (the session object)
     */
    private $_Session;

    /**
     * @var \QUI\Projects\Project (active internal project)
     */
    private $_Project = false;

    /**
     * @var array - list of users (cache)
     */
    private $_users = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_Session = \QUI::getSession();
    }

    /**
     * Return the db table
     *
     * @return String
     */
    static function Table()
    {
        return QUI_DB_PRFX .'users';
    }

    /**
     * Return the db table for the adresses
     *
     * @return String
     */
    static function TableAdress()
    {
        return QUI_DB_PRFX .'users_adress';
    }

    /**
     * Create the database tables for the users
     */
    public function setup()
    {
        $DataBase = \QUI::getDataBase();

        $DataBase->Table()->appendFields(self::Table(), array(
            'id'         => 'int(11)',
            'username'   => 'varchar(50)',
            'password'   => 'varchar(50)',
            'usergroup'  => 'text',
            'firstname'  => 'varchar(40)',
            'lastname'   => 'varchar(40)',
            'usertitle'  => 'varchar(40)',
            'birthday'   => 'varchar(12)',
            'email'      => 'varchar(50)',
            'active'     => 'int(1)',
            'regdate'    => 'int(11)',
            'lastvisit'  => 'int(11)',
            'su'         => 'tinyint(1)',
            'avatar'     => 'text',
            'extra'      => 'text NULL',
            'lang'       => 'varchar(2) NULL',
            'expire'     => 'TIMESTAMP NULL',
            'lastedit'   => 'TIMESTAMP NOT NULL',
            'shortcuts'  => 'varchar(5) NULL',
            'activation' => 'varchar(20) NULL',
            'referal'    => 'varchar(200) NULL',
            'user_agent' => 'text',
            'adress'     => 'int(11)'
        ));

        // Patch
        $DataBase->getPDO()->exec(
            'ALTER TABLE `'. self::Table() .'` CHANGE `birthday` `birthday` DATE NULL DEFAULT NULL'
        );

        // Adresses
        $DataBase->Table()->appendFields(self::TableAdress() , array(
            'id'         => 'int(11)',
            'uid'        => 'int(11)',
            'salutation' => 'varchar(10)',
            'firstname'  => 'varchar(40)',
            'lastname'   => 'varchar(40)',
            'phone'      => 'text',
            'mail'       => 'text',
            'company'    => 'varchar(100)',
            'delivery'   => 'text',
            'street_no'  => 'text',
            'zip'        => 'text',
            'city'       => 'text',
            'country'    => 'text'
        ));

        $DataBase->Table()->setIndex(self::TableAdress(), 'id');

        $DataBase->getPDO()->exec(
            'ALTER TABLE `'. self::TableAdress() .'` CHANGE `id` `id` INT( 11 ) NOT NULL AUTO_INCREMENT'
        );
    }

    /**
     * Is the user authenticated
     *
     * @param \QUI\Users\User|Nobody $User
     * @return Bool
     */
    public function isAuth($User)
    {
        if ( !is_object( $User ) || !$User->getId() ) {
            return false;
        }

        try
        {
            $_User = $this->getUserBySession();
        } catch ( \QUI\Exception $e )
        {
            return false;
        }

        if ( $User->getId() == $_User->getId() ) {
            return true;
        }

        return false;
    }

    /**
     * Is the Object a User?
     *
     * @param unknown_type $User
     * @return Bool
     */
    public function isUser($User)
    {
        if ( !is_object( $User ) ) {
            return false;
        }

        if ( get_class( $User ) === 'QUI\\Users\\User' ) {
            return true;
        }

        return false;
    }

    /**
     * Setzt das interne Projekt
     *
     * Für was???
     *
     * @param \QUI\Projects\Project $Project
     * @deprecated
     */
    public function setProject(\QUI\Projects\Project $Project)
    {
        $this->_Project = $Project;
    }

    /**
     * Gibt das interne Projekt zurück
     *
     *	Für was???
     *
     * @return unknown
     * @deprecated
     */
    public function getProject()
    {
        return $this->_Project;
    }

    /**
     * Create a new User
     *
     * @param String $username - new username [optional]
     * @return \QUI\Users\User
     */
    public function createChild($username=false)
    {
        $newid = $this->_newId();

        if ( $username )
        {
            if ( $this->existsUsername( $username ) )
            {
                throw new \QUI\Exception(
                    \QUI::getLocale()->get(
                        'system',
                        'exception.lib.user.exist'
                    )
                );
            }

            $newname = $username;
        } else
        {
            $newname = 'Neuer Benutzer';
            $i = 0;

            while ( $this->existsUsername( $newname ) )
            {
                $newname = 'Neuer Benutzer ('. $i .')';
                $i++;
            }
        }

        self::checkUsernameSigns( $username );


        // Nur erlaubte Zeichen zu lassen
        //$newname

        \QUI::getDB()->addData(
            self::Table(),
            array(
                'id'       => $newid,
                'username' => $newname,
                'regdate'  => time()
            )
        );

        return $this->get( $newid );
    }

    /**
     * Register a user
     *
     * @param array $params
     *
     * @needle
     * <ul>
     *   <li>$param['username']</li>
     *   <li>$param['password']</li>
     * </ul>
     *
     * @optional
     * <ul>
     *   <li>$param['firstname']</li>
     *	 <li>$param['lastname']</li>
     *	 <li>$param['usertitle']</li>
     *	 <li>$param['birthday']</li>
     *	 <li>$param['email']</li>
     *	 <li>$param['lang']</li>
     *	 <li>$param['expire']</li>
     *	 <li>$param['usergroup']</li>
     * </ul>
     *
     * @todo use bind params
     */
    public function register($params)
    {
        if ( !isset( $params['username'] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.register.specify.username'
                )
            );
        }

        if ( !isset( $params['password'] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'system',
                    ''
                )
            );
        }

        $username = \QUI\Utils\Security\Orthos::clearMySQL( $params['username'] );
        $password = $this->genHash( $params['password'] );

        // unerlaubte zeichen prüfen
        self::checkUsernameSigns( $username );

        if ( $this->existsUsername( $username ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.register.specify.password'
                )
            );
        }

        $regparams = array();
        $optional  = array(
            'firstname',
            'lastname',
            'usertitle',
            'birthday',
            'email',
            'lang',
            'expire',
            'usergroup'
        );

        $rootid = \QUI::conf( 'globals', 'root' );

        foreach ( $optional as $key )
        {
            if ( !isset( $params[ $key ] ) ) {
                continue;
            }

            $value = $params[ $key ];

            // Benutzergruppen gesondert behandeln - darf nicht in die Root Gruppe
            if ( $key == 'usergroup' )
            {
                $_gids = explode( ',', $value );
                $gids  = array();

                foreach ( $_gids as $gid )
                {
                    if ( !empty( $gid ) && $gid != $rootid ) {
                        $gids[] = $gid;
                    }
                }

                $regparams['usergroup'] = ','. implode( ',', $gids ) .',';
                continue;
            }

            $regparams[ $key ] = \QUI\Utils\Security\Orthos::clearMySQL( $params[ $key ] );
        }

        $Session = \QUI::getSession();

        $regparams['id']         = $this->_newId();
        $regparams['su']         = 0;
        $regparams['username']   = $username;
        $regparams['password']   = $password;
        $regparams['active']     = 0;
        $regparams['activation'] = \QUI\Utils\Security\Orthos::getPassword(20);
        $regparams['regdate']    = time();
        $regparams['lastedit']   = date('Y-m-d H:i:s');

        if ( $Session->get( 'ref' ) ) {
            $regparams['referal'] = $Session->get( 'ref' );
        }

        $result = \QUI::getDB()->addData( self::Table(), $regparams );

        return $this->get( (int)$result );
    }

    /**
     * Returns the number of users in the system
     *
     * @return Integer
     */
    public function countAllUsers()
    {
        $result = \QUI::getDB()->select(array(
            'count' => 'count',
            'from' 	=> self::Table()
        ));

        if ( isset( $result[0] ) && isset( $result[0]['count'] ) ) {
            return $result[0]['count'];
        }

        return 0;
    }

    /**
     * Get all users
     *
     * @param Bool $objects - as objects=true, as array=false
     * @return array
     */
    public function getAllUsers($objects=false)
    {
        if ( $objects == false )
        {
            return \QUI::getDB()->select(array(
                'from'  => self::Table(),
                'order' => 'username'
            ));
        }

        $result = array();
        $ids    = $this->getAllUserIds();

        foreach ($ids as $id)
        {
            try
            {
                $result[] = $this->get((int)$id['id']);

            } catch (\QUI\Exception $e)
            {
                // nothing
            }
        }

        return $result;
    }

    /**
     * Returns all userids
     *
     * @return array
     */
    public function getAllUserIds()
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'id',
            'from'   => self::Table(),
            'order'  => 'username'
        ));

        return $result;
    }

    /**
     * Get specific users
     *
     * @param Array $params -> SQL Array
     * @return Array
     */
    public function getUsers($params=array())
    {
        $params['select'] = 'id';
        $params['from']   = self::Table();

        $result = \QUI::getDB()->select($params);

        if (!isset($result[0])) {
            return array();
        }

        $Users = array();

        foreach ($result as $entry)
        {
            try
            {
                $Users[] = $this->get((int)$entry['id']);
            } catch (\QUI\Exception $e)
            {
                // nothing
            }
        }

        return $Users;
    }

    /**
     * Loged in a user
     *
     * @param String $username  - username
     * @param String $pass		- password
     * @return \QUI\Users\User
     * @throws \QUI\Exception
     */
    public function login($username, $pass)
    {
        $username = \QUI\Utils\Security\Orthos::clear( $username );
        //$pass     = \QUI\Utils\Security\Orthos::clear($pass);

        if ( !get_magic_quotes_gpc() )
        {
            $username = addslashes( $username );
            $pass     = addslashes( $pass );
        }

        if ( empty( $pass ) ) {
            throw new \QUI\Exception( 'No Password given', 401 );
        }

        // Authentifizierung
        $auth_type = \QUI::conf( 'auth', 'type' );
        $loginuser = false;

        switch ( $auth_type )
        {
            /**
             * Active Directory Authentifizierung
             */
            case 'AD':
                try
                {
                    $server = \QUI::conf('auth', 'server');
                    $server = explode(';', $server);

                    $Auth = new \QUI\Auth\ActiveDirectory();
                    $Auth->setAttribute('dc', $server);
                    $Auth->setAttribute('base_dn', \QUI::conf('auth', 'base_dn') );
                    $Auth->setAttribute('domain', \QUI::conf('auth', 'domain') );

                    if ($Auth->auth($username, $pass))
                    {
                        $loginuser = \QUI::getDB()->select(array(
                            'from'  => self::Table(),
                            'where' => array(
                                'username' => $username
                            ),
                            'limit' => '0,1'
                        ));
                    }
                } catch ( \QUI\Exception $e )
                {
                    \QUI\Exception::setErrorLog($e->getMessage(), false);
                }

            break;
        }

        if ( $loginuser == false )
        {
            /**
             * Standard Authentifizierung
             */
            if ( \QUI::conf( 'globals', 'emaillogin' ) &&
                strpos( $username, '@' ) !== false )
            {
                // Wenn Login per E-Mail erlaubt ist
                $loginuser = \QUI::getDB()->select(array(
                    'from'  => self::Table(),
                    'where' => array(
                        'email'    => $username,
                        'password' => $this->genHash( $pass )
                    ),
                    'limit' => '0,1'
                ));

            } else
            {
                $loginuser = \QUI::getDB()->select(array(
                    'from'  => self::Table(),
                    'where' => array(
                        'username' => $username,
                        'password' => $this->genHash($pass)
                    ),
                    'limit' => '0,1'
                ));
            }
        }

        if ( isset( $loginuser[0] ) &&
             isset( $loginuser[0]['id'] ) &&
             isset( $loginuser[0]['active'] ) &&
             $loginuser[0]['active'] == 1)
        {
            $uparams = $loginuser[0];

            // Ablaufdatum eines Benutzers
            if ($uparams['expire'] &&
                $uparams['expire'] != '0000-00-00 00:00:00' &&
                strtotime($uparams['expire']) < time())
            {
                throw new \QUI\Exception(
                    \QUI::getLocale()->get('system', 'exception.login.expire', array(
                        'expire' => $uparams['expire']
                    ))
                );
            }

            $User = $this->get( $uparams['id'] );

            // Prüfen ob die Gruppen active sind
            $Groups      = $User->Group;
            $group_check = false;

            foreach ($Groups as $Group)
            {
                if ($Group->getAttribute('active') == 1) {
                    $group_check = true;
                }
            }

            if ($group_check == true)
            {
                $this->_Session->set('auth', 1);
                $this->_Session->set('id', $uparams['id']);

                $useragent = '';

                if (isset($_SERVER['HTTP_USER_AGENT'])) {
                    $useragent = $_SERVER['HTTP_USER_AGENT'];
                }

                \QUI::getDB()->updateData(
                    self::Table(),
                    array(
                        'lastvisit'  => time(),
                        'user_agent' => $useragent
                    ),
                    array('id' => $loginuser[0]['id'])
                );

                $User = $this->get($uparams['id']);
                $this->_users[$uparams['id']] = $User;

                // uid_sess speichern
                $uid_sess_folder = VAR_DIR .'uid_sess/'. $uparams['id'];

                if (file_exists($uid_sess_folder)) {
                    unlink($uid_sess_folder);
                }

                file_put_contents($uid_sess_folder, $this->_Session->getId());

                return $User;
            }
        }

        throw new \QUI\Exception(
            \QUI::getLocale()->get( 'system', 'exception.login.fail' ),
            401
        );
    }

    /**
     * Get the Session user
     *
     * @internal id in Session, bedänklich??
     * @return \QUI\Users\User
     *
     * @todo Sicherheitsabfragen neu schreiben
     */
    public function getUserBySession()
    {
        if ( defined( 'SYSTEM_INTERN' ) ) {
            return $this->getSystemUser();
        }

        // max_life_time check
        if ( !$this->_Session->check() ) {
            return $this->getNobody();
        }

        try
        {
            $User = $this->get(
                $this->_Session->get('id')
            );

            /**
             * Sicherheitsabfragen
             */

            // User Agent
            if (isset($_SERVER['HTTP_USER_AGENT']) &&
                $User->getAttribute('user_agent') != $_SERVER['HTTP_USER_AGENT'] &&
                strpos($_SERVER['HTTP_USER_AGENT'], 'chromeframe') === false)
            {
                // @todo Log
                throw new \QUI\Exception(
                    'Falscher User Agent '. $_SERVER['HTTP_USER_AGENT'] .' -> '. $User->getAttribute('user_agent')
                );
            }

            return $User;

        } catch ( \QUI\Exception $e )
        {
            //\QUI\Exception::setErrorLog($e->getMessage(), false);
        }

        return $this->getNobody();
    }

    /**
     * Return the Nobody user
     *
     * @return \QUI\Users\Nobody
     */
    public function getNobody()
    {
        return new \QUI\Users\Nobody();
    }

    /**
     * Return the System user
     *
     * @return \QUI\Users\SystemUser
     */
    public function getSystemUser()
    {
        return new \QUI\Users\SystemUser();
    }

    /**
     * Get the user by id
     *
     * @param Integer $id
     * @return \QUI\Users\User|false
     */
    public function get($id)
    {
        $id = (int)$id;

        if ( !$id ) {
            return new \QUI\Users\Nobody();
        }

        if ( isset( $this->_users[ $id ] ) ) {
            return $this->_users[ $id ];
        }

        $User = new \QUI\Users\User( $id, $this );
        $this->_users[ $id ] = $User;

        return $User;
    }

    /**
     * get the user by username
     *
     * @param String $username - Username
     * @throws \QUI\Exception
     * @return \QUI\Users\User
     */
    public function getUserByName($username)
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'id',
            'from' 	 => self::Table(),
            'where'  => array(
                'username' => $username
            ),
            'limit' => '1'
        ));

        if ( !isset( $result[0] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get( (int)$result[0]['id'] );
    }

    /**
     * Get the user by email
     *
     * @param String $email - User E-Mail
     * @return \QUI\Users\User
     * @throws \QUI\Exception
     */
    public function getUserByMail($email)
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'id',
            'from' 	 => self::Table(),
            'where'  => array(
                'email' => $email
            ),
            'limit' => '1'
        ));

        if ( !isset( $result[0] ) )
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get(
                    'system',
                    'exception.lib.user.user.not.found'
                ),
                404
            );
        }

        return $this->get( $result[0]['id'] );
    }

    /**
     * Checks if the username already exists
     *
     * @param String $username
     * @return Bool
     */
    public function existsUsername($username)
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'username',
            'from' 	 => self::Table(),
            'where'  => array(
                'username' => $username
            ),
            'limit' => '1'
        ));

        return isset( $result[ 0 ] ) ? true : false;
    }

    /**
     * Checks if the username already exists
     * @param String $username
     *
     * @deprecated
     * use existsUsername
     */
    public function checkUsername($username)
    {
        return $this->existsUsername( $username );
    }

    /**
     * Checks the e-mail if this is already on the system
     *
     * @param String $email
     * @return Bool
     */
    public function existEmail($email)
    {
        $result = \QUI::getDB()->select(array(
            'select' => 'email',
            'from' 	 => self::Table(),
            'where'  => array(
                'email' => $email
            ),
            'limit' => '1'
        ));

        return isset( $result[ 0 ] ) ? true : false;
    }

    /**
     * Generates a hash of a password
     *
     * @param String $pass
     * @return String
     */
    static function genHash($pass)
    {
        $salt = \QUI::conf('globals','salt');

        if ($salt === null)
        {
            $salt = substr(md5(uniqid(rand(), true)), 0, SALT_LENGTH);
        } else
        {
            $salt = substr($salt, 0, SALT_LENGTH);
        }

        return $salt . md5($salt . $pass);
    }

    /**
     * Delete the user
     *
     * @param Integer $id
     * @return Bool
     */
    public function deleteUser($id)
    {
        return $this->get($id)->delete();
    }

    /**
     * Search all users
     *
     * @param Array $params
     * @return Array
     */
    public function search($params)
    {
        return $this->_search($params);
    }

    /**
     * Anzahl der Rechnungen
     *
     * @param array $params - Search parameter
     */
    public function count($params)
    {
        $params['count'] = true;

        unset( $params['limit'] );
        unset( $params['start'] );

        return $this->_search( $params );
    }

    /**
     * Suche ausführen
     *
     * @todo where params
     *
     * @param array $params
     * @return array
     */
    protected function _search($params)
    {
        $DataBase = \QUI::getDB();
        $PDO      = $DataBase->getPDO();
        $params   = \QUI\Utils\Security\Orthos::clearArray( $params );

        $allowOrderFields = array(
            'id'        => true,
            'email'     => true,
            'username'  => true,
            'usergroup' => true,
            'firstname' => true,
            'lastname'  => true,
            'birthday'  => true,
            'active'    => true,
            'regdate'   => true
        );

        $max   = 10;
        $start = 0;

        /**
         * SELECT
         */
        $query = 'SELECT * FROM '. self::Table();
        $binds = array();

        if ( isset( $params['count'] ) ) {
            $query = 'SELECT COUNT( id ) AS count FROM '. self::Table();
        }

        /**
         * WHERE
         */
        if ( isset( $params['where'] ) ) {
            // $_fields['where'] = $params['where'];
        }

        // wenn nicht durchsucht wird dann gelöschte nutzer nicht anzeigen
        if ( !isset( $params['search'] ) ) {
            // $_fields['where_relation']  = "`active` != '-1' ";
        }


        /**
         * WHERE Search
         */
        if ( isset( $params['search'] ) && $params['search'] == true )
        {
            if ( !isset( $params['searchSettings']['filter'] ) ) {
                $params['searchSettings']['filter'] = array();
            }

            if ( !isset( $params['searchSettings']['fields'] ) ) {
                $params['searchSettings']['fields'] = $allowOrderFields;
            }

            $search = $params['searchSettings']['userSearchString'];
            $filter = $params['searchSettings']['filter'];
            $fields = $params['searchSettings']['fields'];

            $filter_status        = false;
            $filter_group         = false;
            $filter_regdate_first = false;
            $filter_regdate_last  = false;

            // set the filters
            if ( isset( $filter[ 'filter_status' ] ) &&
                 $filter[ 'filter_status' ] != 'all' )
            {
                $filter_status = true;
            }

            if ( isset( $filter[ 'filter_group' ] ) &&
                 !empty( $filter[ 'filter_group' ] ) )
            {
                $filter_group = true;
            }

            if ( isset( $filter[ 'filter_regdate_first' ] ) &&
                 !empty( $filter[ 'filter_regdate_first' ] ) )
            {
                $filter_regdate_first = true;
            }

            if ( isset( $filter['filter_regdate_last'] ) &&
                 !empty( $filter['filter_regdate_last'] ) )
            {
                $filter_regdate_last = true;
            }


            // create the search
            $query .= ' WHERE (';
            $binds[':search'] = '%'. $search .'%';

            foreach ( $fields as $field => $value )
            {
                if ( !isset( $allowOrderFields[ $field ] ) ) {
                    continue;
                }

                $query .= ' '. $field .' LIKE :search OR ';
            }

            if ( substr( $query, -3 ) == 'OR ' ) {
                $query = substr( $query, 0, -3 );
            }

            $query.= ') ';


            if ( $filter_status )
            {
                $query .= ' AND active = :active';
                $binds[':active'] = (int)$filter['filter_status'];
            }


            if ( $filter_group )
            {
                $groups = explode( ',', $filter['filter_group'] );

                foreach ( $groups as $groupId )
                {
                    if ( (int)$groupId > 0 )
                    {
                        $query .= ' AND usergroup LIKE "%:'. $groupId .'%" ';
                        $binds[':'. $groupId] = (int)$groupId;
                    }
                }
            }


            if ( $filter_regdate_first )
            {
                $query .= ' AND regdate >= :firstreg ';
                $binds[':firstreg'] = \QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_first'] .' 00:00:00'
                );
            }


            if ( $filter_regdate_last )
            {
                $query .= " AND regdate <= :lastreg ";
                $binds[':lastreg'] = \QUI\Utils\Convert::convertMySqlDatetime(
                    $filter['filter_regdate_last' ] .' 00:00:00'
                );
            }
        }


        /**
         * ORDER
         */
        if ( isset( $params['order'] ) &&
             isset( $params['field'] ) &&
             $params['field'] &&
             isset( $allowOrderFields[ $params['field'] ] ) )
        {
            $query .= ' ORDER BY '. $params['field'] .' '. $params['order'];
        }

        /**
         * LIMIT
         */
        if ( isset( $params['limit'] ) || isset( $params['start'] ) )
        {
            if ( isset( $params['limit'] ) ) {
                $max = (int)$params['limit'];
            }

            if ( isset( $params['start'] ) ) {
                $start = (int)$params['start'];
            }

            $query .= ' LIMIT '. $start .', '. $max;
        }


        $Statement = $PDO->prepare( $query );

        foreach ( $binds as $key => $value ) {
            $Statement->bindParam( $key, $value, \PDO::PARAM_STR );
        }

        try
        {
            $Statement->execute();
        } catch ( \PDOException $e )
        {
            $message  = $e->getMessage();
            $message .= print_r($query, true);

            throw new \QUI\Database\Exception(
                $message,
                $e->getCode()
            );
        }

        $result = $Statement->fetchAll( \PDO::FETCH_ASSOC );

        if ( isset( $params['count'] ) ) {
            return (int)$result[0]['count'];
        }

        return $result;
    }

    /**
     * Gibt eine neue Benutzer Id zwischen 100 und 1000000000 zurück
     *
     * @return Integer
     */
    protected function _newId()
    {
        $create = true;

        while ($create)
        {
            srand(microtime()*1000000);
              $newid = rand(100, 1000000000);

              $result = \QUI::getDB()->select(array(
                'from'  => self::Table(),
                'where' => array(
                    'id' => $newid
                  )
            ));

            if (isset($result[0]) && $result[0]['id'])
            {
                $create = true;
                continue;
            }

            $create = false;
        }

        return $newid;
    }

    /**
     * Delete illegal characters from the name
     *
     * @param String $username
     * @return Bool
     */
    static function clearUsername($username)
    {
        return preg_replace('/[^a-zA-Z0-9-_äöüß]/', '', $username);
    }

    /**
     * Checks name for illegal characters
     *
     * @param String $username
     * @return Bool
     * @throws \QUI\Exception
     */
    static function checkUsernameSigns($username)
    {
        if ($username != self::clearUsername($username))
        {
            throw new \QUI\Exception(
                \QUI::getLocale()->get('system', 'exception.lib.user.illegal.signs')
            );
        }

        return true;
    }
}
