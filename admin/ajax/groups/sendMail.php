<?php

use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'ajax_groups_sendMail',
    static function ($groupId, $mailSubject, $mailContent): void {
        $Group = QUI::getGroups()->get($groupId);
        $mailSubject = trim(Orthos::clear($mailSubject));
        $mailContent = trim($mailContent);

        $users = $Group->getUsers([
            'select' => 'uuid, email'
        ]);

        $recipients = [];

        foreach ($users as $user) {
            if (empty($user['uuid']) || !isset($user['email'])) {
                continue;
            }

            $User = QUI::getUsers()->get($user['uuid']);
            $Address = $User->getStandardAddress();
            $countryName = '';
            $countryCode = (string)($Address?->getAttribute('country') ?? '');

            if ($countryCode !== '') {
                try {
                    $countryName = QUI::getCountries()->get($countryCode)->getName();
                } catch (QUI\Exception $Exception) {
                    QUI\System\Log::writeDebugException($Exception);
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
                '[user_username]' => (string)($User->getAttribute('username') ?? ''),
                '[group_title]' => $Group->getName(),
                '[group_uuid]' => $Group->getUUID(),
                '[group_id]' => (string)$Group->getId()
            ];

            $userEmails = explode(',', $user['email']);

            foreach ($userEmails as $email) {
                $email = trim($email);

                if ($email === '') {
                    continue;
                }

                $recipients[strtolower($email)] = [
                    'email' => $email,
                    'placeholders' => $placeholders
                ];
            }
        }

        if (empty($recipients)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'controls.SendGroupMail.no_recipients')
            );
        }

        foreach ($recipients as $recipient) {
            $Mailer = new \QUI\Mail\Mailer();
            $Mailer->addRecipient($recipient['email']);
            $Mailer->setSubject(strtr($mailSubject, $recipient['placeholders']));
            $Mailer->setHTML(true);
            $Mailer->setBody(strtr($mailContent, $recipient['placeholders']));
            $Mailer->send();
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.ajax.groups.sendMail.success',
                [
                    'group' => $Group->getName() . ' (#' . $Group->getId() . ')',
                    'count' => count($recipients)
                ]
            )
        );
    },
    ['groupId', 'mailSubject', 'mailContent'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.send_mail']
);
