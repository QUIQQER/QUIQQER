<?php

/**
 * This file contains Users_Nobody
 */

/**
 * The standard user
 * Nobody has no rights
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */
class Users_Nobody extends QDOM implements Interface_Users_User
{
    /**
     * constructor
     */
	public function __construct()
	{
		$this->setAttribute('username', 'nobody');
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isSU()
	 */
	public function isSU() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isAdmin()
	 */
	public function isAdmin() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isDeleted()
	 */
	public function isDeleted() {
	    return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isActive()
	 */
	public function isActive() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::isOnline()
	 */
	public function isOnline() {
	    return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::logout()
	 */
	public function logout() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::activate()
	 *
	 * @param String $code - activasion code [optional]
	 */
	public function activate($code) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_User::deactivate()
	 */
	public function deactivate() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::disable()
	 */
	public function disable() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::save()
	 */
	public function save() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::delete()
	 */
	public function delete() {
	    return false;
	}

	/**
	 * This method is useless for nobody
	 *
	 * @param array $params
	 * @throws QException
	 * @ignore
	 */
	public function addAdress($params)
	{
		throw new QException(
		    QUI::getLocale('system', 'exception.lib.user.nobody.add.adress')
		);
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getExtra()
	 *
	 * @param String $field
	 * @return false
	 */
	public function getExtra($field) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getType()
	 *
	 * @return String
	 */
	public function getType() {
	    return get_class($this);
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getId()
	 *
	 * @return false
	 */
	public function getId() {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getName()
	 *
	 * @return String
	 */
	public function getName() {
	    return $this->getAttribute('username');
	}

	/**
	 * Return the user lang
	 * @return String
	 */
    public function getLang() {
        return QUI::getLocale()->getCurrent();
    }

    /**
	 * Return the locale object depending on the user
	 * @return QUI_Locale
	 */
    public function getLocale() {
        return QUI::getLocale();
    }

    /**
     * This method is useless for nobody
     * Users_Nobody cannot have a adress
     *
     * @return array
     * @ignore
     */
	public function getAdressList() {
	    return array();
	}

	/**
	 * This method is useless for nobody
     * Users_Nobody cannot have a adress
     *
     * @param Integer $id
	 * @throws QException
	 * @ignore
	 */
	public function getAdress($id)
	{
	    throw new QException(
	        QUI::getLocale('system', 'exception.lib.user.nobody.get.adress')
	    );
	}

	/**
	 * This method is useless for nobody
     * Users_Nobody cannot have a adress
     *
     * @return false
     * @ignore
	 */
	public function getStandardAdress() {
	    return false;
	}

    /**
     * (non-PHPdoc)
     * @see Interface_Users_User::getStatus()
     */
	public function getStatus() {
	    return true;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setGroups()
	 *
	 * @param String|array
	 */
	public function setGroups($groups) {
	    return false;
    }

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getGroups()
	 *
	 * @param Bool $array - returns the groups as objects (true) or as an array (false)
	 */
	public function getGroups($array=true) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getAvatar()
	 * @param Bool $url - get the avatar with the complete url string
	 */
	public function getAvatar($url=false) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::getPermission()
	 *
	 * @param String $right
     * @param array $ruleset - optional, you can specific a ruleset, a rules = array with rights
     *
	 */
	public function getPermission($right, $ruleset=false) {
	    // @todo "Jeder" Gruppe muss im System vorhanden sein
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setExtra()
	 *
	 * @param String $field
	 * @param String|Integer|array $value
	 */
	public function setExtra($field, $value) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::loadExtra()
	 *
	 * @param Projects_Project $Project
	 */
	public function loadExtra(Projects_Project $Project) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::setPassword()
	 *
	 * @param String $new - new password
	 */
	public function setPassword($new) {
	    return false;
	}

	/**
	 * (non-PHPdoc)
	 * @see Interface_Users_User::checkPassword()
	 *
	 * @param String $pass 		- Password
	 * @param Bool $encrypted	- is the given password already encrypted?
	 *
	 * @return false
	 */
	public function checkPassword($pass, $encrypted=false) {
	    return false;
	}
}


?>