<?php

use QUI\Mail\UserMailPlaceholders;
use QUI\Utils\Security\Orthos;

QUI::getAjax()->registerFunction(
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

            $userEmails = explode(',', $user['email']);

            foreach ($userEmails as $email) {
                $email = trim($email);

                if ($email === '') {
                    continue;
                }

                $recipients[strtolower($email)] = [
                    'email' => $email,
                    'placeholders' => $userPlaceholders
                ];
            }
        }

        if (empty($recipients)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'controls.SendGroupMail.no_recipients')
            );
        }

        $groupPlaceholders = [
            '[group_title]' => $Group->getName(),
            '[group_uuid]' => $Group->getUUID(),
            '[group_id]' => (string)$Group->getId()
        ];

        foreach ($recipients as $recipient) {
            $Mailer = new \QUI\Mail\Mailer();
            $Mailer->addRecipient($recipient['email']);
            $Mailer->setSubject($recipient['placeholders']->replace($mailSubject, $groupPlaceholders));
            $Mailer->setHTML(true);
            $Mailer->setBody($recipient['placeholders']->replace($mailContent, $groupPlaceholders));
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
