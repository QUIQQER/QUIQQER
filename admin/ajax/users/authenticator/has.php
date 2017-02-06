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
    function ($uid, $authenticator) {
        return QUI::getUsers()->get($uid)->hasAuthenticator($authenticator);

    },
    array('uid', 'authenticator'),
    'Permission::checkAdminUser'
);
