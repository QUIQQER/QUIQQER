<?php

/**
 * Set a password for the user
 *
 * @param {string|integer} $uid
 * @param {string} $pw1
 * @param {string} $pw2
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_set_password',
    function ($uid, $pw1, $pw2) {
        $Users = QUI::getUsers();
        $User  = $Users->get((int)$uid);

        if ($pw1 != $pw2) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.wrong.passwords'
                )
            );
        }

        $User->setPassword($pw1);
    },
    array('uid', 'pw1', 'pw2', 'params')
);
