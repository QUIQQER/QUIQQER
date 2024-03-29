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

QUI::$Ajax->registerFunction(
    'ajax_users_authenticator_sendPasswordResetConfirmMail',
    function ($email) {
        try {
            $User = QUI::getUsers()->getUserByMail($email);
        } catch (\Exception $Exception) {
            return;
        }

        try {
            Handler::getInstance()->sendPasswordResetVerificationMail($User);
        } catch (QUI\Users\Auth\Exception $Exception) {
            throw $Exception;
        } catch (\Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            throw new QUI\Users\Auth\Exception([
                'quiqqer/quiqqer',
                'exception.user.auth.send_password_reset_mail_error'
            ]);
        }
    },
    ['email']
);
