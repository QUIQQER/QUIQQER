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
        $Address = $User->getStandardAddress();
        $countryName = '';
        $countryCode = (string)($Address?->getAttribute('country') ?? '');

        if ($countryCode !== '') {
            try {
                $countryName = QUI::getCountries()->get($countryCode)->getName();
            } catch (QUI\Exception $Exception) {
                QUI\System\Log::addError($Exception->getMessage());
            }
        }

        $placeholders = [
            '[user_uuid]' => (string)$User->getUUID(),
            '[user_id]' => (string)$User->getId(),
            '[user_salutation]' => (string)($Address?->getAttribute('salutation') ?? ''),
            '[user_firstname]' => (string)($Address?->getAttribute('firstname') ?? ''),
            '[user_lastname]' => (string)($Address?->getAttribute('lastname') ?? ''),
            '[user_street_no]' => (string)($Address?->getAttribute('street_no') ?? ''),
            '[user_city]' => (string)($Address?->getAttribute('city') ?? ''),
            '[user_country]' => $countryName,
            '[user_email]' => (string)($User->getAttribute('email') ?? ''),
            '[user_company]' => (string)($Address?->getAttribute('company') ?? ''),
            '[user_zip]' => (string)($Address?->getAttribute('zip') ?? ''),
            '[user_username]' => (string)($User->getAttribute('username') ?? '')
        ];

        $Mailer = new Mailer();

        $Mailer->addRecipient($User->getAttribute('email'));
        $Mailer->setSubject(strtr($mailSubject, $placeholders));
        $Mailer->setHTML(true);
        $Mailer->setBody(strtr($mailContent, $placeholders));

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
