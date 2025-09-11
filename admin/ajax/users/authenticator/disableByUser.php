<?php

/**
 * Deactivate an authenticator from the session user
 *
 * @param string $authenticator
 * @throws QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_disableByUser',
    static function ($authenticator): bool {
        $User = QUI::getUserBySession();
        $User->disableAuthenticator($authenticator);
        return true;
    },
    ['authenticator'],
    'Permission::checkUser'
);
