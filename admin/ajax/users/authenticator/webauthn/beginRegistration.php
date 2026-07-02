<?php

use QUI\Users\Auth\WebAuthn\Server;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_beginRegistration',
    static function ($name = '', $userUuid = ''): array {
        $User = QUI::getUserBySession();

        if (QUI::getUsers()->isNobodyUser($User) && QUI::getSession()->get('uid')) {
            $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        }

        if (QUI::getUsers()->isNobodyUser($User)) {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.login.fail.user.not.found'],
                404
            );
        }

        if ($userUuid !== '' && $userUuid !== $User->getUUID()) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        return (new Server())->getRegistrationOptions($User, $name);
    },
    ['name', 'userUuid']
);
