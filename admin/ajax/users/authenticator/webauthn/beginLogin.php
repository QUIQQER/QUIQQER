<?php

use QUI\Users\Auth\WebAuthn\Server;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_webauthn_beginLogin',
    static function ($username = ''): array {
        $User = null;

        if (QUI::getSession()->get('auth-primary') && QUI::getSession()->get('uid')) {
            $User = QUI::getUsers()->get(QUI::getSession()->get('uid'));
        } elseif (!empty($username)) {
            if (QUI::conf('globals', 'emaillogin') && str_contains($username, '@')) {
                try {
                    $User = QUI::getUsers()->getUserByMail($username);
                } catch (QUI\Exception) {
                    $User = null;
                }
            }

            try {
                if (!$User) {
                    $User = QUI::getUsers()->getUserByName($username);
                }
            } catch (QUI\Exception) {
                $User = null;
            }
        }

        return (new Server())->getAuthenticationOptions($User);
    },
    ['username']
);
