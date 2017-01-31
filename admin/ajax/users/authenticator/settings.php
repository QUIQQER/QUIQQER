<?php

/**
 * Deactivate a authenticatior from the user
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @throws \QUI\Users\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_settings',
    function ($uid, $authenticator) {
        $User          = QUI::getUsers()->get($uid);
        $Authenticator = $User->getAuthenticator($authenticator);
        $Settings      = $Authenticator->getSettingsControl();
\QUI\System\Log::writeRecursive($Settings);
        if ($Settings) {
            return $Settings->create();
        }

        return '';
    },
    array('uid', 'authenticator'),
    'Permission::checkAdminUser'
);
