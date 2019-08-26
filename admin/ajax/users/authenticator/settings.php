<?php

/**
 * Return the settings control from an authenticator
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @return string
 * @throws \QUI\Users\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_settings',
    function ($uid, $authenticator) {
        $User          = QUI::getUsers()->get($uid);
        $Authenticator = $User->getAuthenticator($authenticator);
        $Settings      = $Authenticator->getSettingsControl();

        if ($Settings) {
            return $Settings->create();
        }

        return '';
    },
    array('uid', 'authenticator'),
    'Permission::checkAdminUser'
);
