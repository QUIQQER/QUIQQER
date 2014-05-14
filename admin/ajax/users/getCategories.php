<?php

/**
 * Gibt die Button für den Benutzer zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_users_getCategories($uid)
{
    $Users = \QUI::getUsers();
    $User  = $Users->get( (int)$uid );

    $Toolbar = \QUI\Users\Utils::getUserToolbar( $User );

    return $Toolbar->toArray();
}

\QUI::$Ajax->register(
    'ajax_users_getCategories',
    array('uid'),
    'Permission::checkSU'
);
