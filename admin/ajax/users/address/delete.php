<?php

/**
 * Delete a address
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_users_address_delete',
    function ($uid, $aid) {
        $User    = QUI::getUsers()->get((int)$uid);
        $Address = $User->getAddress((int)$aid);

        $Address->delete();
    },
    array('uid', 'aid'),
    'Permission::checkSU'
);
