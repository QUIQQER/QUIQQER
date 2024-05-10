<?php

/**
 * Check if the given user has an email address
 *
 * @param string $email
 * @return boolean
 */

QUI::$Ajax->registerFunction(
    'ajax_users_hasEmail',
    static function ($userId): bool {
        $User = QUI::getUsers()->get($userId);
        $email = $User->getAttribute('email');

        return !empty($email);
    },
    ['userId'],
    'Permission::checkAdminUser'
);
