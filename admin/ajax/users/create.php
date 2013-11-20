<?php

/**
 * Benutzer mit Benutzernamen anlegen
 *
 * @param String
 * @return User-ID
 */
function ajax_users_create($username)
{
    $Users = \QUI::getUsers();
	$User  = $Users->createChild($username);

	return $User->getId();
}
QUI::$Ajax->register('ajax_users_create', array('username'), 'Permission::checkUser')

?>