<?php

/**
 * Set new user password and send via email
 *
 * @param int $userId - QUIQQER User Id
 * @param string $newPassword - New password
 * @param bool $forceNew - Force password reset after first login
 * @return void
 *
 * @throws QUI\Exception
 */

use QUI\Mail\Mailer;

QUI::$Ajax->registerFunction(
    'ajax_user_setAndSendPassword',
    static function ($userId, $newPassword, $forceNew): void {
        $User = QUI::getUsers()->get($userId);
        $User->setPassword($newPassword);

        $forceNew = !empty($forceNew);

        if ($forceNew) {
            $User->setAttribute('quiqqer.set.new.password', true);
            $User->save(QUI::getUsers()->getSystemUser());
        }

        QUI::getMessagesHandler()->clear();

        // send mail
        $Mailer = new Mailer();
        $email = $User->getAttribute('email');

        if (empty($email)) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'message.ajax.user.setAndSendPassword.no_mail_sent',
                    [
                        'user' => $User->getName() . ' (#' . $User->getUUID() . ')'
                    ]
                )
            );

            return;
        }

        $Locale = $User->getLocale();

        $Mailer->addRecipient($email);

        $Mailer->setSubject(
            $Locale->get(
                'quiqqer/core',
                'mails.user.new_password.subject'
            )
        );

        $forceNewMsg = '';

        if ($forceNew) {
            $forceNewMsg = $Locale->get(
                'quiqqer/core',
                'mails.user.new_password.body.force_new'
            );
        }

        $body = $Locale->get(
            'quiqqer/core',
            'mails.user.new_password.body',
            [
                'name' => $User->getName(),
                'password' => $newPassword,
                'forceNewMsg' => $forceNewMsg
            ]
        );

        $Mailer->setBody($body);
        $Mailer->send();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.ajax.user.setAndSendPassword.success',
                [
                    'user' => $User->getName() . ' (#' . $User->getUUID() . ')'
                ]
            )
        );
    },
    ['userId', 'newPassword', 'forceNew'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
