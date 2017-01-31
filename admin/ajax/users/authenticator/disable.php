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
    function ($uid, $authenticator) {
        $User = QUI::getUsers()->get($uid);
        $User->disableAuthenticator($authenticator);
    },
    array('uid', 'authenticator'),
    'Permission::checkAdminUser'
);
