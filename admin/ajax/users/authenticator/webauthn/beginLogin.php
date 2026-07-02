<?php

use QUI\Users\Auth\WebAuthn\Server;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_beginLogin',
    static function (): array {
        $User = null;

        if (QUI::getSession()->get('auth-primary') && QUI::getSession()->get('uid')) {
            $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        }

        return (new Server())->getAuthenticationOptions($User);
    },
    []
);
