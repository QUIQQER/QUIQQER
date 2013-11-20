<?php

/**
 * Adress Liste eines Benutzers
 *
 * @return Array
 */
function ajax_users_adress_list($uid)
{
	$User = \QUI::getUsers()->get((int)$uid);

	$adresses = $User->getAdressList();
	$result   = array();

	foreach ($adresses as $Adress)
	{
		$entry        = $Adress->getAllAttributes();
		$entry['id']  = $Adress->getId();
		$entry['uid'] = $User->getId();

		$result[] = $entry;
	}

	return $result;
}
QUI::$Ajax->register('ajax_users_adress_list', array('uid'), 'Permission::checkSU');

?>