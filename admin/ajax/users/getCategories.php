<?php

/**
 * Gibt die Button für den Benutzer zurück
 *
 * @param string / Integer $uid
 *
 * @return array
 */
function ajax_users_getCategories($uid)
{
    $Users = QUI::getUsers();
    $User  = $Users->get((int)$uid);

    $Toolbar = QUI\Users\Utils::getUserToolbar($User);

    return $Toolbar->toArray();
}

QUI::$Ajax->register(
    'ajax_users_getCategories',
    array('uid'),
    'Permission::checkSU'
);
