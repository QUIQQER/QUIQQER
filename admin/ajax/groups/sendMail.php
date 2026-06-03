<?php

use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'ajax_groups_sendMail',
    static function ($groupId, $mailSubject, $mailContent): void {
        $Group = QUI::getGroups()->get($groupId);
        $mailSubject = trim(Orthos::clear($mailSubject));
        $mailContent = trim($mailContent);

        $users = $Group->getUsers([
            'select' => 'email'
        ]);

        $emails = [];

        foreach ($users as $user) {
            if (!isset($user['email'])) {
                continue;
            }

            $userEmails = explode(',', $user['email']);

            foreach ($userEmails as $email) {
                $email = trim($email);

                if ($email === '') {
                    continue;
                }

                $emails[strtolower($email)] = $email;
            }
        }

        $recipients = array_values($emails);

        if (empty($recipients)) {
            throw new QUI\Exception(
                QUI::getLocale()->get('quiqqer/core', 'controls.SendGroupMail.no_recipients')
            );
        }

        foreach ($recipients as $recipient) {
            $Mailer = new \QUI\Mail\Mailer();
            $Mailer->addRecipient($recipient);
            $Mailer->setSubject($mailSubject);
            $Mailer->setHTML(true);
            $Mailer->setBody($mailContent);
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
