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
use QUI\Projects\Media\Utils as MediaUtils;
use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'ajax_user_sendMail',
    static function ($userId, $mailSubject, $mailContent): void {
        $User = QUI::getUsers()->get($userId);
        $mailSubject = trim(Orthos::clear($mailSubject));
        $mailContent = trim($mailContent);

        // send mail
        $Mailer = new Mailer();

        // Fetch image URLs and replace with fully qualified URLs
        preg_match_all('#"(image\.php.*)"#i', $mailContent, $matches);

        if (!empty($matches[1])) {
            $baseUrl = QUI::getRewrite()->getProject()->get(1)->getUrlRewrittenWithHost();
            $baseUrl = rtrim($baseUrl, '/');

            foreach ($matches[1] as $mediaUrl) {
                $mailContent = str_replace(
                    $mediaUrl,
                    $baseUrl . MediaUtils::getRewrittenUrl($mediaUrl),
                    $mailContent
                );
            }
        }

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
