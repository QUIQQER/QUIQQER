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
    'ajax_users_set_passwordChange',
    static function ($uid, $newPassword, $passwordRepeat, $oldPassword) {
        $Users = QUI::getUsers();
        $User = $Users->get($uid);

        if ($newPassword != $passwordRepeat) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.lib.user.wrong.passwords'
                )
            );
        }

        $User->changePassword($newPassword, $oldPassword);
    },
    ['uid', 'newPassword', 'passwordRepeat', 'oldPassword']
);
