<?php

/**
 * Return an address from an user
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */
function ajax_users_address_get($uid, $aid)
{
    $User    = QUI::getUsers()->get((int)$uid);
    $Address = $User->getAddress((int)$aid);

    return $Address->getAttributes();
}

QUI::$Ajax->register(
    'ajax_users_address_get',
    array('uid', 'aid'),
    'Permission::checkSU'
);
