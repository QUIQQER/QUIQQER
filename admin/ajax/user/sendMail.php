<?php

/**
 * Send an e-mail to a QUIQQER user
 *
 * @param int $userId - QUIQQER User Id
 * @return array
 *
 * @throws QUI\Exception
 */

use QUI\Mail\Mailer;
use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'ajax_user_sendMail',
    static function ($userId, $mailSubject, $mailContent): void {
        $User = QUI::getUsers()->get($userId);
        $mailSubject = trim(Orthos::clear($mailSubject));
        $mailContent = trim($mailContent);

        $Mailer = new Mailer();

        $Mailer->addRecipient($User->getAttribute('email'));
        $Mailer->setSubject($mailSubject);
        $Mailer->setHTML(true);
        $Mailer->setBody($mailContent);

        $Mailer->send();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.ajax.user.sendMail.success',
                [
                    'user' => $User->getName() . ' (#' . $User->getUUID() . ')'
                ]
            )
        );
    },
    ['userId', 'mailSubject', 'mailContent'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.send_mail']
);
