<?php

/**
 * Return the address as HTML
 *
 * @param integer|string $uid - id of the user
 * @param integer|string $aid - id of the address
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_users_address_display',
    function ($uid, $aid) {
        $User    = QUI::getUsers()->get((int)$uid);
        $Address = $User->getAddress((int)$aid);

        return $Address->getDisplay();
    },
    array('uid', 'aid'),
    'Permission::checkAdminUser'
);
