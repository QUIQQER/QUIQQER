<?php

/**
 * Adresse eines Benutzers löschen
 *
 * @return Array
 */
function ajax_users_adress_delete($uid, $aid)
{
	$User   = QUI::getUsers()->get((int)$uid);
	$Adress = $User->getAdress((int)$aid);

	$Adress->delete();
}
QUI::$Ajax->register('ajax_users_adress_delete', array('uid', 'aid'), 'Permission::checkSU');

?>