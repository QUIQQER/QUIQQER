<?php

/**
 * Checks if the given email address is already taken by a QUIQQER user
 *
 * @param string $email
 * @return boolean
 */

QUI::$Ajax->registerFunction(
    'ajax_users_emailExists',
    static function ($email): bool {
        return QUI::getUsers()->emailExists($email);
    },
    ['email'],
    'Permission::checkUser'
);
