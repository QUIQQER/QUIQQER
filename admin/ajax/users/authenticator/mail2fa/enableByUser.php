<?php

/**
 * Send mail with OTP mail code to the current session user
 */

use QUI\Users\Auth\VerifiedMail2FA;

QUI::getAjax()->registerFunction(
    'ajax_users_authenticator_mail2fa_enableByUser',
    static function ($code): void {
        VerifiedMail2FA::enableByUser($code);
    },
    ['code']
);
