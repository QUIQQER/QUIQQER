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

QUI::$Ajax->registerFunction(
    'ajax_user_setAndSendPassword',
    function ($userId, $newPassword, $forceNew) {
        $User = QUI::getUsers()->get((int)$userId);
        $User->setPassword($newPassword);

        $forceNew = !empty($forceNew);

        if ($forceNew) {
            $User->setAttribute('quiqqer.set.new.password', true);
            $User->save(QUI::getUsers()->getSystemUser());
        }

        QUI::getMessagesHandler()->clear();

        // send mail
        $Mailer = new \QUI\Mail\Mailer();
        $email = $User->getAttribute('email');

        if (empty($email)) {
            QUI::getMessagesHandler()->addAttention(
                QUI::getLocale()->get(
                    'quiqqer/quiqqer',
                    'message.ajax.user.setAndSendPassword.no_mail_sent',
                    [
                        'user' => $User->getName() . ' (#' . $User->getId() . ')'
                    ]
                )
            );

            return;
        }

        $Locale = $User->getLocale();

        $Mailer->addRecipient($email);

        $Mailer->setSubject(
            $Locale->get(
                'quiqqer/quiqqer',
                'mails.user.new_password.subject'
            )
        );

        $forceNewMsg = '';

        if ($forceNew) {
            $forceNewMsg = $Locale->get(
                'quiqqer/quiqqer',
                'mails.user.new_password.body.force_new'
            );
        }

        $body = $Locale->get(
            'quiqqer/quiqqer',
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
                'quiqqer/quiqqer',
                'message.ajax.user.setAndSendPassword.success',
                [
                    'user' => $User->getName() . ' (#' . $User->getId() . ')'
                ]
            )
        );
    },
    ['userId', 'newPassword', 'forceNew'],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
