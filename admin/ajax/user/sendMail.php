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
use QUI\Mail\UserMailPlaceholders;
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

        $userPlaceholders = new UserMailPlaceholders(
            [
                'uuid' => $User->getUUID(),
                'id' => $User->getId(),
                'email' => $User->getAttribute('email'),
                'username' => $User->getAttribute('username')
            ],
            [
                'salutation' => $Address?->getAttribute('salutation'),
                'firstname' => $Address?->getAttribute('firstname'),
                'lastname' => $Address?->getAttribute('lastname'),
                'street_no' => $Address?->getAttribute('street_no'),
                'city' => $Address?->getAttribute('city'),
                'company' => $Address?->getAttribute('company'),
                'zip' => $Address?->getAttribute('zip')
            ],
            $countryName
        );

        $Mailer = new Mailer();

        $Mailer->addRecipient($User->getAttribute('email'));
        $Mailer->setSubject($userPlaceholders->replace($mailSubject));
        $Mailer->setHTML(true);
        $Mailer->setBody($userPlaceholders->replace($mailContent));

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
