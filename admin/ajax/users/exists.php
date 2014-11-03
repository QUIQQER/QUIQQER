<?php

/**
 * check, if this is a username which can be used
 *
 * @param String
 * @return Bool
 */

function ajax_users_exists($username)
{
    return \QUI::getUsers()->existsUsername( $username );
}

\QUI::$Ajax->register(
    'ajax_users_exists',
    array('username'),
    'Permission::checkUser'
);
