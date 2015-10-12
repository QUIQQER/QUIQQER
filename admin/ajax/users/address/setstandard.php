<?php

/**
 * Set the address as standard address
 *
 * @param Integer|String $uid - id of the user
 * @param Integer|String $aid - id of the address
 *
 * @return Array
 */
function ajax_users_address_setstandard($uid, $aid)
{
    $User = QUI::getUsers()->get((int)$uid);
    $Address = $User->getAddress((int)$aid);

    $User->setAttribute('address', $Address->getId());
    $User->save();
}

QUI::$Ajax->register(
    'ajax_users_address_setstandard',
    array('uid', 'aid'),
    'Permission::checkSU'
);
