<?php

/**
 * Send mail with OTP mail code to the current session user
 */

use QUI\Users\Auth\VerifiedMail2FA;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_sendVerifiedMail2faMail',
    static function (): void {
        VerifiedMail2FA::sendAuthMailToSessionUser();
    },
    false
);
