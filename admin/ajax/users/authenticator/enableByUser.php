<?php

/**
 * Activate an authenticator from the session user
 *
 * @param string $authenticator
 * @throws QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_enableByUser',
    static function ($authenticator): bool {
        $User = QUI::getUserBySession();
        $User->enableAuthenticator($authenticator);
        return true;
    },
    ['authenticator'],
    'Permission::checkUser'
);
