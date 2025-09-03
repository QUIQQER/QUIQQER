<?php

/**
 * Send mail with OTP mail code to the current session user
 */

use QUI\Users\Auth\VerifiedMail2FA;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_mail2fa_sendEnableMail',
    static function (): void {
        VerifiedMail2FA::sendEnableMailToSessionUser();
    },
    false,
    ['Permission::checkUser']
);
