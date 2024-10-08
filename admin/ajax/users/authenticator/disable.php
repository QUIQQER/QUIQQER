<?php

/**
 * Deactivate a authenticatior from the user
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @throws \QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_disable',
    static function ($uid, $authenticator): void {
        $User = QUI::getUsers()->get($uid);
        $User->disableAuthenticator($authenticator);
    },
    ['uid', 'authenticator'],
    'Permission::checkAdminUser'
);
