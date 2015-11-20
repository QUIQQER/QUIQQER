<?php

/**
 * This file contains \QUI\Auth\ActiveDirectory
 *
 * @deprecated
 */

namespace QUI\Auth;

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_NORMAL_ACCOUNT', 805306368);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_WORKSTATION_TRUST', 805306369);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_INTERDOMAIN_TRUST', 805306370);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_SECURITY_GLOBAL_GROUP', 268435456);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_DISTRIBUTION_GROUP', 268435457);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_SECURITY_LOCAL_GROUP', 536870912);

/**
 * ADLDAP_NORMAL_ACCOUNT
 * @var integer
 * @package com.pcsg.qui.auth.activedirectory
 */
define('ADLDAP_DISTRIBUTION_LOCAL_GROUP', 536870913);

/**
 * PCSG Auth Active Directory Klasse
 * Baut eine Veindung zu einem MS Active Directory Dienst über LDAP auf.
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @package com.pcsg.qui.auth
 *
 * @example
 * $Auth = new \QUI\Auth\ActiveDirectory();
 * $Auth->setAttribute('dc', array("192.168.1.1","192.168.2.100"));
 * $Auth->setAttribute('base_dn', 'DC=SBS2003,DC=local');
 * $Auth->setAttribute('domain', 'SBS2003.local');
 * $Auth->setAttribute('_use_ssl', true);
 * $Auth->setAttribute('auth_user', 'username');
 * $Auth->setAttribute('auth_password', passwort);
 * $Auth->setAttribute('_sortUser', true);
 *
 * $Auth->auth('username', 'password');
 *
 * @todo code style
 * @todo docu translation
 */

class ActiveDirectory extends \QUI\QDOM implements \QUI\Interfaces\Users\Auth
{
    /**
     * ldap_bind
     * @var ldap_bind
     */
    private $_bind = false;

    /**
     * ldap_connect
     * @var ldap_connect
     */
    private $_conn = false;

    /**
     * _base_dn attributes
     * @var string|false
     */
    private $_base_dn = false;

    /**
     * ldap domain
     * @var string|false
     */
    private $_domain = false;

    /**
     * domain ldap controller
     * @var string
     */
    private $_domain_controller;

    /**
     * group list
     * @var array|false
     */
    private $_recursive_groups = true;

    /**
     * user list
     * @var array
     */
    private $_users;

    /**
     * user group list
     * @var array
     */
    private $_user_groups = false;

    /**
     * Konstruktor
     */
    public function __construct()
    {
        if( !function_exists('ldap_connect') )
        {
            throw new \QUI\Exception(
                'LDAP Functions not exist',
                404
            );
        }

        // default
        $this->setAttribute('use_ssl', false);
        $this->setAttribute('auth_user', false);
        $this->setAttribute('auth_password', false);
    }

    /**
     * Destructor
     *
     * Close the ldap connection
     */
    public function __destruct()
    {
        if ($this->_conn) {
            ldap_close ($this->_conn);
        }
    }

    /**
     * Aufbau zum AD Server
     *
     * @throws \QUI\Exception
     */
    protected function _connect()
    {
        $dc = $this->_randomController();

        if ($this->getAttribute('use_ssl'))
        {
            $this->_conn = ldap_connect("ldaps://".$dc);
        } else
        {
            $this->_conn = ldap_connect($dc);
        }

        if (!$this->_conn)
        {
            throw new \QUI\Exception(
                'LDAP Connect failed'
            );
        }

        ldap_set_option($this->_conn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($this->_conn, LDAP_OPT_REFERRALS, 0);
    }

    /**
     * Anmeldung am DC Server
     *
     * @throws \QUI\Exception
     */
    protected function _logon()
    {
        if(	$this->getAttribute('auth_user') &&
            $this->getAttribute('auth_password') &&
            $this->getAttribute('domain'))
        {
            $this->_bind = @ldap_bind(
                $this->_conn,
                $this->getAttribute('auth_user').'@'.$this->getAttribute('domain'),
                $this->getAttribute('auth_password')
            );

            if(!$this->_bind) {
                throw new \QUI\Exception('Login false, check username, password and domain');
            }

        }

        throw new \QUI\Exception('Setup a auth_user, auth_password and Domain');
    }

    /**
     * Wählt einen zufalls Controller aus der dem Controller array
     *
     * @return string - DC Server
     * @throws \QUI\Exception
     */
    protected function _randomController()
    {
        if($this->getAttribute('dc') && is_array($this->getAttribute('dc')) )
        {
            $randomkey = array_rand( $this->getAttribute('dc') );
            $dc =$this->getAttribute('dc');
            $this->_domain_controller =  $dc[0];

            return $this->_domain_controller;
        }

        throw new \QUI\Exception('\QUI\Auth\ActiveDirectory :: Setup a DC Host Controller');
    }

    /**
     * Auth über AD
     *
     * @param string $username - username
     * @param string $password - password
     * @return boolean
     */
    public function auth($username, $password)
    {
        if (!$this->_conn) {
            $this->_connect();
        }

        try
        {
            $this->_bind = ldap_bind(
                $this->_conn,
                $username .'@'. $this->getAttribute('domain'),
                $password
            );

        } catch ( \QUI\Exception $e )
        {
            \QUI\System\Log::writeException($e);
        }

        if ($this->_bind) {
            return true;
        }

        return false;
    }

    /**
     * Gibt User Informationen aus der AD zurück
     *
     * @param string $username
     * @param array|boolean $fields - optional
     * @param boolean $showall - optional
     * @return array
     */
    public function getUser($username, $fields=false, $showall=false)
    {
        $this->checkConn();

        $filter = "samaccountname=". $username;

        if (!$fields && !$showall)
        {
            $fields = array(
                "samaccountname",
                "mail",
                "username",
                "sn",
                "sn",
                "title",
                "description",
                "postalcode",
                "telephonenumber",
                "co",
                "streetaddress",
                "wwwhomepage",
                "name",
                "company",
                "department",
                "displayname",
                "telephonenumber",
                "primarygroupid",
                "telefax",
                "mobile"
            );

            $ldret = ldap_search($this->_conn, $this->_base_dn, $filter, $fields);
        } else
        {
            $ldret = ldap_search($this->_conn, $this->_base_dn, $filter);
        }

        $ret = ldap_get_entries($this->_conn, $ldret);

        if ($this->getAttribute('_sortUser'))
        {
            $sort_ret = array();

            foreach ($fields as $field)
            {
                if (isset($ret[0][$field][0]))
                {
                    $sort_ret[$field] = $ret[0][$field][0];
                } else
                {
                    $sort_ret[$field] = '';
                }
            }

            $sort_ret['username'] = $ret[0]['samaccountname'][0];
            $name = explode(' ',$ret[0]['name'][0]);

            if(isset($name[0])) {
                $sort_ret['firstname'] = $name[0];
            }

            if(isset($name[1])) {
                $sort_ret['lastname'] = $name[1];
            }

            $ret = $sort_ret;
        }

        return $ret;
    }

    /**
     * Enter description here...
     *
     * @param string $username
     * @param string $recursive
     * @return array
     */
    public function getUserGroups($username,$recursive=NULL)
    {
        $this->checkConn();

        if ($username==NULL) {
            return (false);
        }

        if ($recursive==NULL) {
            $recursive=$this->_recursive_groups;
        }

        $info =@ $this->getUser($username, array("memberof", "primarygroupid"));

        if (isset($info[0]["memberof"]))
        {
            $groups=$this->nice_names($info[0]["memberof"]);
        } else
        {
            return false;
        }

        if ($recursive)
        {
            foreach ($groups as $id => $group_name)
            {
                $extra_groups=$this->recursive_groups($group_name);
                $groups=array_merge($groups,$extra_groups);
            }
        }

        return ($groups);
    }

    /**
     * Gibt alle AD User als array zurück
     * wenn eine Gruppe angegeben ist werden nur die Benutzer einer Gruppe zurück gegebe
     *
     * @param string $group - wenn gesetzt werden nur mitglieder aus dieser Gruppe zurück gegeben
     * @param string $search - wenn gehetztwird nach dem string im benutzernamen gesucht
     * @return array
     */
    public function getUsers($group=false, $search = "*")
    {
        $this->checkConn();

        $include_desc = false;
        $sorted = true;
        $filter = "(&(objectClass=user)(samaccounttype=". ADLDAP_NORMAL_ACCOUNT .")(objectCategory=person)(cn=".$search."))";

        $fields = array("samaccountname","displayname");
        $sr = ldap_search($this->_conn, $this->_base_dn, $filter, $fields);
        $entries = ldap_get_entries($this->_conn, $sr);

        $users_array = array();

        for ($i=0; $i<$entries["count"]; $i++)
        {
            if ($include_desc && strlen($entries[$i]["displayname"][0])>0)
            {
                $users_array[ $entries[$i]["samaccountname"][0] ] = $entries[$i]["displayname"][0];
            } elseif ($include_desc)
            {
                $users_array[ $entries[$i]["samaccountname"][0] ] = $entries[$i]["samaccountname"][0];
            } else
            {
                array_push($users_array, $entries[$i]["samaccountname"][0]);
            }
        }

        if ($sorted) {
            asort($users_array);
        }

        if($group)
        {
            foreach ($users_array as $key => $user)
            {
                if(!$this->userInGroup($user,$group)) {
                    unset($users_array[$key]);
                }
            }
        }

        return ($users_array);
    }

    /**
     * Enter description here...
     *
     * @param unknown_type $group_name
     * @param unknown_type $fields
     * @return unknown
     */
    public function getGroup($group_name,$fields=NULL)
    {
        $this->checkConn();

        if ($group_name==NULL){
            return (false);
        }

        $filter="(&(objectCategory=group)(name=".$group_name."))";
        //echo ($filter."!!!<br>");
        if ($fields == NULL) {
            $fields = array("member", "memberof", "cn", "description", "distinguishedname", "objectcategory", "samaccountname");
        }

        $sr = ldap_search($this->_conn, $this->_base_dn, $filter, $fields);
        $ret = ldap_get_entries($this->_conn, $sr);

        return ($ret);
    }

    /**
     * Enter description here...
     * @todo not implemented
     */
    public function getGroups()
    {

    }

    /**
     * Prüft ob der Benutzer in der angegebenen AD gruppe ist
     *
     * @param unknown_type $username
     * @param unknown_type $group
     * @param unknown_type $recursive
     * @return unknown
     */
    public function userInGroup($username,$group,$recursive=NULL)
    {
        $this->checkConn();

        if ($username==NULL){
            return false;
        }

        if ($group==NULL){
            return false;
        }

        if ($recursive==NULL){
            $recursive=$this->_recursive_groups;
        } //use the default option if they haven't set it

        $groups = $this->getUserGroups($username, array("memberof"), $recursive);

        if (!is_array($groups)) {
            return false;
        }

        if (in_array($group,$groups)) {
            return true;
        }

        return false;
    }

    /**
     * Enter description here...
     *
     * @param string $group
     * @return unknown
     */
    public function recursive_groups($group)
    {
        if ($group==NULL) {
            return false;
        }

        $ret_groups = array();
        $groups=$this->getGroup($group, array("memberof"));

        if (isset($groups[0]["memberof"]))
        {
                $groups=$groups[0]["memberof"];
        } else
        {
            $groups = array();
        }

        if ($groups)
        {
            $group_names = $this->nice_names($groups);
            $ret_groups = array_merge($ret_groups, $group_names); //final groups to return

            foreach ($group_names as $id => $group_name)
            {
                $child_groups = $this->recursive_groups($group_name);
                $ret_groups = array_merge($ret_groups, $child_groups);
            }
        }

        return ($ret_groups);
    }

    /**
     * Löscht alle AD zeichen(eg. CN, DN)
     * @param array $groups
     * @return array
     */
    private function nice_names($groups)
    {
        $group_array=array();

        for ($i = 0; $i < $groups["count"]; $i++)
        {
            $line = $groups[$i];

            if (strlen($line) > 0)
            {
                $bits = explode(",",$line);
                $group_array[] = substr( $bits[0], 3, (strlen($bits[0])-3) );
            }
        }

        return ($group_array);
    }

    /**
     * Prüft ob die verbindung zum Ldap Server besteht
     * wenn keine verbindung besteht wird ssie aufgebaut
     *
     * @throws \QUI\Exception
     */
    private function checkConn()
    {
        if (!$this->_conn) {
            $this->_connect();
        }

        if (!$this->_bind) {
            $this->_logon();
        }

        if (!$this->_base_dn)
        {
            if ( $this->getAttribute('base_dn') )
            {
                $this->_base_dn = $this->getAttribute('base_dn');
            } else
            {
                throw new \QUI\Exception(
                    '\QUI\Auth\ActiveDirectory :: Setup a Domain Name "setAttribute(base_dn)" '
                );
            }
        }

        return true;
    }
}
