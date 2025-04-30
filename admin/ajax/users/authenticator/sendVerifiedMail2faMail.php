<?php

/**
 * Send mail to confirm password reset process
 *
 * @param integer|string $uid
 * @param string $authenticator
 * @return void
 * @throws \QUI\Users\Exception
 */

use QUI\Users\Auth\Handler;
use QUI\Users\Auth\VerifiedMail2FA;

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_sendVerifiedMail2faMail',
    static function (): void {
        VerifiedMail2FA::sendAuthMailToSessionUser();
    },
    false
);
