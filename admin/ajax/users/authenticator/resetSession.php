<?php

/**
 * Reset authentication state after a failed secondary authentication attempt.
 */

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_resetSession',
    static function (): void {
        QUI::getSession()->remove('inAuthentication');
        QUI::getSession()->remove('auth-primary');
    }
);
