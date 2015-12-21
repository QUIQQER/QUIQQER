<?php

/**
 * Gibt die Button für den Benutzer zurück
 *
 * @param string / Integer $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_getCategories',
    function ($uid) {
        $Users = QUI::getUsers();
        $User  = $Users->get((int)$uid);

        $Toolbar = QUI\Users\Utils::getUserToolbar($User);

        return $Toolbar->toArray();
    },
    array('uid'),
    'Permission::checkSU'
);
