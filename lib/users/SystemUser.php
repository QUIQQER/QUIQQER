<?php

/**
 * This file contains Users_SystemUser
 */

/**
 * the system user
 * Can change things but can not in the admin and can query any ajax functions
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.users
 */

class Users_SystemUser extends Users_Nobody implements Interface_Users_User
{
    /**
     * construtcor
     */
	public function __construct()
	{
		$this->setAttribute('username', 'system');
	}

	/**
	 * (non-PHPdoc)
	 * @see Users_Nobody::getId()
	 */
	public function getId() { return 5; }
}

?>