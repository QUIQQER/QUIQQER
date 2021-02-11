<?php

use QUI\Utils\Security\Orthos;

/**
 * Send an e-mail to a QUIQQER user
 *
 * @param int $userId - QUIQQER User Id
 * @return array
 *
 * @throws QUI\Exception
 */
QUI::$Ajax->registerFunction(
    'ajax_user_sendMail',
    function ($userId, $mailSubject, $mailContent) {
        $User        = QUI::getUsers()->get((int)$userId);
        $mailSubject = \trim(Orthos::clear($mailSubject));
        $mailContent = \trim(Orthos::cleanHTML($mailContent));

        // send mail
        $Mailer = new \QUI\Mail\Mailer();

        $Mailer->addRecipient($User->getAttribute('email'));
        $Mailer->setSubject($mailSubject);
        $Mailer->setBody($mailContent);

        $Mailer->send();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'message.ajax.user.sendMail.success',
                [
                    'user' => $User->getName().' (#'.$User->getId().')'
                ]
            )
        );
    },
    ['userId', 'mailSubject', 'mailContent'],
    'Permission::checkAdminUser'
);
