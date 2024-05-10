<?php

/**
 * check, if this is a username which can be used
 *
 * @param string $username
 *
 * @return boolean
 */

QUI::$Ajax->registerFunction(
    'ajax_users_exists',
    static fn($username): bool => QUI::getUsers()->usernameExists($username),
    ['username'],
    'Permission::checkUser'
);
