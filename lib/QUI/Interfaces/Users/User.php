<?php

/**
 * This file contains \QUI\Interfaces\Users\User
 */

namespace QUI\Interfaces\Users;

/**
 * The user interface
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */

interface User
{
    /**
     * Is the user superuser?
     *
     * @return boolean
     */
    public function isSU();

    /**
     * @param integer $groupId
     * @return boolean
     */
    public function isInGroup($groupId);

    /**
     * the user can use the backend?
     *
     * @return boolean
     */
    public function canUseBackend();

    /**
     * Loged the user out
     */
    public function logout();

    /**
     * Activate the user
     *
     * @param string $code - activasion code [optional]
     */
    public function activate($code);

    /**
     * Deactivate the user
     */
    public function deactivate();

    /**
     * Disable a user
     * The user data will be lost, but the user still exist
     *
     * @param \QUI\Users\User|boolean $ParentUser
     */
    public function disable($ParentUser = false);

    /**
     * Save all attributes of the user
     *
     * @param \QUI\Users\User|boolean $ParentUser
     */
    public function save($ParentUser = false);

    /**
     * Delete the user
     */
    public function delete();

    /**
     * Returns the user id
     *
     * @return integer
     */
    public function getId();

    /**
     * @return string|bool
     */
    public function getUniqueId();

    /**
     * Returns the name of the user
     * If the user has an first and Lastname, it returns the "Firstname Lastname".
     * otherwise it returns getUsername()
     *
     * @return string
     */
    public function getName();

    /**
     * Returns the username
     *
     * @return string
     */
    public function getUsername();

    /**
     * Return the user language
     *
     * @return string
     */
    public function getLang();

    /**
     * Returns the Locale object depending on the user
     *
     * @return \QUI\Locale
     */
    public function getLocale();

    /**
     * Returns the class type
     *
     * @return string (\QUI\Users\Nobody|\QUI\Users\SystemUser|\QUI\Users\User)
     */
    public function getType();

    /**
     * Returns the activ status of the user
     * is the user active or not?
     *
     * @return boolean
     */
    public function getStatus();

    /**
     * Has the user the right?
     *
     * @param string $right
     * @param array|boolean $ruleset - (optional), you can specific a ruleset, a rules = array with rights
     *
     * @return boolean
     */
    public function getPermission($right, $ruleset = false);

    /**
     * set a group to the user
     *
     * @param array|string $groups
     */
    public function setGroups($groups);

    /**
     * Returns all groups in which the user is
     *
     * @param boolean $array - returns the groups as objects (true) or as an array (false)
     *
     * @return array
     */
    public function getGroups($array = true);

    /**
     * Get an address from the user
     *
     * @param integer $id - ID of the address
     *
     * @return \QUI\Users\Address
     *
     * @throws \QUI\Exception
     */
    public function getAddress($id);

    /**
     * Return the Country from the user
     *
     * @return \QUI\Countries\Country|boolean
     */
    public function getCountry();

    /**
     * Remove an attribute
     *
     * @param string $key
     */
    public function removeAttribute($key);

    /**
     * Set a attribute of the user
     *
     * @param string $key
     * @param string|integer|array $value
     */
    public function setAttribute($key, $value);

    /**
     * set multiple attributes
     *
     * @param array $attributes
     */
    public function setAttributes($attributes);

    /**
     * Get a attribute of the user
     *
     * @param string $var
     *
     * @return string|integer|array
     */
    public function getAttribute($var);

    /**
     * Return all attributes
     *
     * @return array
     */
    public function getAttributes();

    /**
     * Returns the avatar of the user
     *
     * @return \QUI\Projects\Media\Image|false
     */
    public function getAvatar();

    /**
     * Set the password of the user
     *
     * @param string $new - new password
     * @param \QUI\Users\User|boolean $ParentUser
     */
    public function setPassword($new, $ParentUser = false);

    /**
     * Checks the password if it's the user from
     *
     * @param string $pass - Password
     * @param boolean $encrypted - is the given password already encrypted?
     */
    public function checkPassword($pass, $encrypted = false);

    /**
     * Is the user deleted?
     *
     * @return boolean
     */
    public function isDeleted();

    /**
     * is the user active?
     *
     * @return boolean
     */
    public function isActive();

    /**
     * is the user online at the moment?
     *
     * @return boolean
     */
    public function isOnline();

    /**
     * Is the user a compny?
     *
     * @return mixed
     */
    public function isCompany();

    /**
     * Set the company status, whether the user is a company or not
     *
     * @param boolean $status - true or false
     */
    public function setCompanyStatus($status);

    /**
     * Add the user to a group
     *
     * @param integer $groupId
     */
    public function addToGroup($groupId);

    /**
     * Remove a group from the user
     *
     * @param \QUI\Groups\Group|integer $Group
     */
    public function removeGroup($Group);

    /**
     * refresh the data from the database
     *
     * @throws \QUI\Users\Exception
     */
    public function refresh();
}
