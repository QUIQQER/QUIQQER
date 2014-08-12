<?php

/**
 * This file contains \QUI\Interfaces\Users\User
 */

namespace QUI\Interfaces\Users;

/**
 * The user interface
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.interface.users
 */

interface User
{
    /**
     * Is the user superuser?
     * @return Bool
     */
    public function isSU();

    /**
     * Is the user admin?
     * @return Bool
     */
    public function isAdmin();

    /**
     * Loged the user out
     */
    public function logout();

    /**
     * Activate the user
     *
     * @param String $code - activasion code [optional]
     */
    public function activate($code);

    /**
     * Deactivate the user
     */
    public function deactivate();

    /**
     * Disable a user
     * The user data will be lost, but the user still exist
     */
    public function disable();


    /**
     * Save all attributes of the user
     *
     * @param \QUI\Users\User|false $ParentUser
     */
    public function save($ParentUser=false);

    /**
     * Delete the user
     */
    public function delete();

    /**
     * Returns the user id
     * @return Integer
     */
    public function getId();

    /**
     * Returns the username, the attribute username
     * @return String
     */
    public function getName();

    /**
     * Return the user language
     * @return String
     */
    public function getLang();

    /**
     * Returns the Locale object depending on the user
     * @return \QUI\Locale
     */
    public function getLocale();

    /**
     * Returns the class type
     * @return String (\QUI\Users\Nobody|\QUI\Users\SystemUser|\QUI\Users\User)
     */
    public function getType();

    /**
     * Returns the activ status of the user
     * is the user active or not?
     *
     * @return Bool
     */
    public function getStatus();

    /**
     * Has the user the right?
     *
     * @param String $right
     * @param array $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
     * @return Bool
     */
    public function getPermission($right, $ruleset=false);

    /**
     * set a group to the user
     *
     * @param array|String $groups
     */
    public function setGroups($groups);

    /**
     * Returns all groups in which the user is
     *
     * @param Bool $array - returns the groups as objects (true) or as an array (false)
     * @return array
     */
    public function getGroups($array=true);

    /**
     * Get an address from the user
     *
     * @param Integer $id - ID of the address
     * @return \QUI\Users\Address
     */
    public function getAddress($id);

    /**
     * Return the Country from the user
     *
     * @return \QUI\Countries\Country|boolean
     */
    public function getCountry();

    /**
     * Set a attribute of the user
     *
     * @param String $key
     * @param String|Integer|Array $value
     */
    public function setAttribute($key, $value);

    /**
     * set multible attributes
     * @param Array $attributes
     */
    public function setAttributes($attributes);

    /**
     * Get a attribute of the user
     *
     * @param String $var
     * @return String|Ineteger|array
     */
    public function getAttribute($var);

    /**
     * Returns the avatar of the user
     *
     * @param Bool $url - get the avatar with the complete url string
     * @return String
     */
    public function getAvatar($url=false);

    /**
     * Set the password of the user
     *
     * @param String $new - new password
     * @param \QUI\Users\User|false $ParentUser
     */
    public function setPassword($new, $ParentUser=false);

    /**
     * Checks the password if it's the user from
     *
     * @param String $pass 		- Password
     * @param Bool $encrypted	- is the given password already encrypted?
     */
    public function checkPassword($pass, $encrypted=false);

    /**
     * Is the user deleted?
     * @return Bool
     */
    public function isDeleted();

    /**
     * is the user active?
     * @return Bool
     */
    public function isActive();

    /**
     * is the user online at the moment?
     * @return Bool
     */
    public function isOnline();
}
