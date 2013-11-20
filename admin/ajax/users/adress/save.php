<?php

/**
 * Adresse eines Benutzers speichern oder anlegen
 *
 * @return
 */
function ajax_users_adress_save($uid, $aid, $data)
{
    $data = json_decode($data, true);
	$User = \QUI::getUsers()->get((int)$uid);

	try
	{
	    $Adress = $User->getAdress((int)$aid);
	} catch (\QUI\Exception $e)
	{
        $Adress = $User->addAdress($data);
	}

	$Adress->clearMail();
	$Adress->clearPhone();

	if (isset($data['mails']) && is_array($data['mails']))
	{
		foreach ($data['mails'] as $mail) {
			$Adress->addMail($mail);
		}
	}

	if (isset($data['phone']) && is_array($data['phone']))
	{
		foreach ($data['phone'] as $phone) {
			$Adress->addPhone($phone);
		}
	}

	unset($data['mails']);
	unset($data['phone']);

	$Adress->setAttributes($data);
	$Adress->save();
}
QUI::$Ajax->register('ajax_users_adress_save', array('uid', 'aid', 'data'), 'Permission::checkSU');

?>