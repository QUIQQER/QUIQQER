<?php

/**
 * Adresse eines Benutzers
 *
 * @return Array
 */
function ajax_users_adress_get($uid, $aid)
{
	$User   = QUI::getUsers()->get((int)$uid);
    $Adress = $User->getAdress((int)$aid);

    return $Adress->getAttributes();
}
QUI::$Ajax->register('ajax_users_adress_get', array('uid', 'aid'), 'Permission::checkSU');

?>