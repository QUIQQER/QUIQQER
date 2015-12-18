<?php

/**
 * check, if this is a username which can be used
 *
 * @param string $username
 *
 * @return boolean
 */

function ajax_users_exists($username)
{
    return QUI::getUsers()->usernameExists($username);
}

QUI::$Ajax->register(
    'ajax_users_exists',
    array('username'),
    'Permission::checkUser'
);
