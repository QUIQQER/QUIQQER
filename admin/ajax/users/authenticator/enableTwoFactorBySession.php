<?php

/**
 * Activate an authenticator from the session user
 *
 * @param string $authenticator
 * @throws QUI\Users\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_enableTwoFactorBySession',
    static function ($authenticator): bool {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User)) {
            $User = QUI::getUsers()->get(
                QUI::getSession()->get('uid')
            );
        }

        $User->enableAuthenticator($authenticator, QUI::getUsers()->getSystemUser());
        return true;
    },
    ['authenticator']
);
