<?php

/**
 * Deactivate an authenticator from the user
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @throws \QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_enable',
    static function ($uid, $authenticator): void {
        $User = QUI::getUsers()->get($uid);
        $User->enableAuthenticator($authenticator);
    },
    ['uid', 'authenticator'],
    'Permission::checkAdminUser'
);
