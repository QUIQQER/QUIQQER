<?php

/**
 * Benutzer mit Benutzernamen anlegen
 *
 * @param string $username - name of the user
 *
 * @return integer User-ID
 */

QUI::$Ajax->registerFunction(
    'ajax_users_create',
    static function ($username): string|int {
        $Users = QUI::getUsers();
        $User = $Users->createChild($username);

        return $User->getUUID();
    },
    ['username'],
    'Permission::checkUser'
);
