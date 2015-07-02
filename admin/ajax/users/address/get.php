<?php

/**
 * Return an address from an user
 *
 * @param Integer|String $uid - id of the user
 * @param Integer|String $aid - id of the address
 *
 * @return Array
 */
function ajax_users_address_get($uid, $aid)
{
    $User = QUI::getUsers()->get((int)$uid);
    $Address = $User->getAddress((int)$aid);

    return $Address->getAttributes();
}

QUI::$Ajax->register(
    'ajax_users_address_get',
    array('uid', 'aid'),
    'Permission::checkSU'
);
