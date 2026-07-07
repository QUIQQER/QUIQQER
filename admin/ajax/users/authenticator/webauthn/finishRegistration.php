<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\Server;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_finishRegistration',
    static function ($attestation, $name = '', $userUuid = ''): array {
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

        $attestation = json_decode($attestation, true);

        if (!is_array($attestation)) {
            throw new QUI\Users\Auth\Exception(
                ['quiqqer/core', 'exception.webauthn.attestation_missing'],
                400
            );
        }

        $result = (new Server())->finishRegistrationForUser($User, $attestation, $name);
        $User->enableAuthenticator(WebAuthnAuthenticator::class, QUI::getUsers()->getSystemUser());

        return $result;
    },
    ['attestation', 'name', 'userUuid']
);
