<?php

/**
 * Adresse eines Benutzers als standard Adresse setzen
 *
 * @return Array
 */
function ajax_users_adress_setstandard($uid, $aid)
{
	$User   = \QUI::getUsers()->get((int)$uid);
	$Adress = $User->getAdress((int)$aid);

	$User->setAttribute('adress', $Adress->getId());
    $User->save();
}
QUI::$Ajax->register('ajax_users_adress_setstandard', array('uid', 'aid'), 'Permission::checkSU');

?>