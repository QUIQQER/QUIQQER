<?php

/**
 * Delete a address
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */
function ajax_users_address_delete($uid, $aid)
{
    $User = QUI::getUsers()->get((int)$uid);
    $Address = $User->getAddress((int)$aid);

    $Address->delete();
}

QUI::$Ajax->register(
    'ajax_users_address_delete',
    array('uid', 'aid'),
    'Permission::checkSU'
);
