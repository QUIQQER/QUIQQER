<?php

/**
 * Deactivate a authenticatior from the user
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @throws \QUI\Users\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_enable',
    function ($uid, $authenticator) {
        $User = QUI::getUsers()->get($uid);
        $User->enableAuthenticator($authenticator);
    },
    array('uid', 'authenticator'),
    'Permission::checkAdminUser'
);
