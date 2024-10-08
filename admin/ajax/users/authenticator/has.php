<?php

/**
 * Has the user the authenticator enabled?
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @throws \QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_has',
    static function ($uid, $authenticator): bool {
        return QUI::getUsers()->get($uid)->hasAuthenticator($authenticator);
    },
    ['uid', 'authenticator'],
    'Permission::checkAdminUser'
);
