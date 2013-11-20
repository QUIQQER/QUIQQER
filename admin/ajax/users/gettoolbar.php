<?php

/**
 * Gibt die Button für den Benutzer zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_users_gettoolbar($uid)
{
	$Users = \QUI::getUsers();
	$User  = $Users->get( (int)$uid );

	$Toolbar = Users_Utils::getUserToolbar( $User );

	return $Toolbar->toArray();
}
QUI::$Ajax->register('ajax_users_gettoolbar', array('uid'), 'Permission::checkSU')

?>