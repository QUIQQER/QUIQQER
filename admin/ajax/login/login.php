<?php

/**
 * user login
 *
 * @param string $username - name of the user / email of the user
 * @param string $password - password
 * @return array
 */
function ajax_login_login($username, $password)
{
    QUI::getUsers()->login($username, $password);

    return QUI::getUserBySession()->getAttributes();
}

QUI::$Ajax->register(
    'ajax_login_login',
    array('username', 'password')
);
