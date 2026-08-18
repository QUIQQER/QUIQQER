<?php

use QUI\Mail\Mailer;
use QUI\Utils\Security\Orthos;

QUI::getAjax()->registerFunction(
    'ajax_email_getRenderedHtml',
    static function ($mailSubject, $mailContent): string {
        $mailSubject = trim(Orthos::clear($mailSubject));
        $mailContent = trim($mailContent);

        $Mailer = new Mailer();
        $Mailer->setSubject($mailSubject);
        $Mailer->setHTML(true);
        $Mailer->setBody($mailContent);

        return $Mailer->getRenderedBody();
    },
    ['mailSubject', 'mailContent'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.send_mail']
);
