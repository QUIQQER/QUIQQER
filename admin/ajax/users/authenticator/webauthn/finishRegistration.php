<?php

use QUI\Users\Auth\WebAuthn as WebAuthnAuthenticator;
use QUI\Users\Auth\WebAuthn\Server;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_finishRegistration',
    static function ($attestation, $name = '', $userUuid = ''): array {
        $Server = new Server();
        $User = $Server->getAuthorizedEnrollmentUser();

        if ($userUuid !== '' && (string)$userUuid !== (string)$User->getUUID()) {
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

        $result = $Server->finishRegistrationForUser($User, $attestation, $name);
        $User->enableAuthenticator(WebAuthnAuthenticator::class, QUI::getUsers()->getSystemUser());

        return $result;
    },
    ['attestation', 'name', 'userUuid']
);
