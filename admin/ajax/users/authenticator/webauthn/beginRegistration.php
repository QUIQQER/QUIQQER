<?php

use QUI\Users\Auth\WebAuthn\Server;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_beginRegistration',
    static function ($name = '', $userUuid = ''): array {
        $Server = new Server();
        $User = $Server->getAuthorizedEnrollmentUser();

        if ($userUuid !== '' && (string)$userUuid !== (string)$User->getUUID()) {
            throw new QUI\Permissions\Exception(
                ['quiqqer/core', 'exception.no.permission'],
                403
            );
        }

        return $Server->getRegistrationOptions($User, $name);
    },
    ['name', 'userUuid']
);
