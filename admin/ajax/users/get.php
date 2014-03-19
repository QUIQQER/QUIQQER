<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_users_get($uid)
{
    return \QUI::getUsers()->get( (int)$uid )->getAllAttributes();
}

QUI::$Ajax->register(
    'ajax_users_get',
    array('uid'),
    'Permission::checkSU'
);
