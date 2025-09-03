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
    static function ($uid, $authenticator): string {
        $User = QUI::getUsers()->get($uid);

        if (QUI::isFrontend()) {
            $available = QUI\Users\Auth\Handler::getInstance()->getAvailableAuthenticators();
            $available = array_flip($available);

            if (!isset($available[$authenticator]) && $available[$authenticator]) {
                return '';
            }

            $Authenticator = new $authenticator($User);
        } else {
            $Authenticator = $User->getAuthenticator($authenticator);
        }

        $Settings = $Authenticator->getSettingsControl();

        if ($Settings) {
            return $Settings->create();
        }

        return '';
    },
    ['uid', 'authenticator'],
    'Permission::checkAdminUser'
);
