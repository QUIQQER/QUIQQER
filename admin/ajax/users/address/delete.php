<?php

/**
 * Delete a address
 *
 * @param Integer|String $uid - id of the user
 * @param Integer|String $aid - id of the address
 *
 * @return Array
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
