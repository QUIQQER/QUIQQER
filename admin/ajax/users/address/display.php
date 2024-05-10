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
    static function ($uid, $aid) {
        $User = QUI::getUsers()->get($uid);
        $Address = $User->getAddress($aid);

        return $Address->getDisplay();
    },
    ['uid', 'aid'],
    'Permission::checkAdminUser'
);
