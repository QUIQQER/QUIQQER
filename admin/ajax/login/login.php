<?php

/**
 * user login
 *
 * @param string $username - name of the user / email of the user
 * @param string $password - password
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_login_login',
    function ($username, $password) {
        QUI::getUsers()->login($username, $password);

        return QUI::getUserBySession()->getAttributes();
    },
    array('username', 'password')
);
