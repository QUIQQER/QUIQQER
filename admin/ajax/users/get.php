<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_users_get($uid)
{
	$Users = \QUI::getUsers();
	$User  = $Users->get((int)$uid);

	return $User->getAllAttributes();
}
QUI::$Ajax->register('ajax_users_get', array('uid'), 'Permission::checkSU')

?>