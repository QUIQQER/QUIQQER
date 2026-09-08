<?php

use QUI\Users\Auth\WebAuthn\Server;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_webauthn_beginUserRegistration',
    static function ($username, $displayName = '', $name = ''): array {
        $username = trim($username);
        $displayName = trim($displayName);

        if ($username === '') {
            throw new QUI\Users\Exception(
                ['quiqqer/core', 'exception.webauthn.username_missing'],
                400
            );
        }

        return (new Server())->getRegistrationOptionsForNewUser($username, $displayName ?: $username, $name);
    },
    ['username', 'displayName', 'name']
);
