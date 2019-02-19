<?php

/**
 * Return an address from an user
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_address_get',
    function ($uid, $aid) {
        $User    = QUI::getUsers()->get((int)$uid);
        $Address = $User->getAddress((int)$aid);

        return $Address->getAttributes();
    },
    array('uid', 'aid'),
    'Permission::checkAdminUser'
);
