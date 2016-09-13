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
    function ($uid, $newPassword, $passwordRepeat, $oldPassword) {
        $Users = QUI::getUsers();
        $User  = $Users->get((int)$uid);

        if ($newPassword != $passwordRepeat) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.lib.user.wrong.passwords'
                )
            );
        }

        $User->changePassword($newPassword, $oldPassword);
    },
    array('uid', 'newPassword', 'passwordRepeat', 'oldPassword')
);
