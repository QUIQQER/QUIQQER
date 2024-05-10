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
    static function ($uid, $pw1, $pw2) {
        if (empty($pw1) || empty($pw2)) {
            return;
        }

        $Users = QUI::getUsers();
        $User = $Users->get($uid);

        if ($pw1 != $pw2) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.wrong.passwords'
                )
            );
        }

        $User->setPassword($pw1);
    },
    ['uid', 'pw1', 'pw2'],
    'Permission::checkAdminUser'
);
