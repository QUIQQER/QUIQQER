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
function ajax_users_set_password($uid, $pw1, $pw2)
{
    $Users = QUI::getUsers();
    $User = $Users->get((int)$uid);

    if ($pw1 != $pw2) {
        throw new QUI\Exception(
            QUI::getLocale(
                'quiqqer/system',
                'exception.lib.user.wrong.passwords'
            )
        );
    }

    $User->setPassword($pw1);
}

QUI::$Ajax->register(
    'ajax_users_set_password',
    array('uid', 'pw1', 'pw2', 'params'),
    'Permission::checkSU'
);
