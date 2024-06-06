<?php

/**
 * test mail settings
 */

QUI::$Ajax->registerFunction(
    'ajax_system_mailTest',
    static function ($params): void {
        $params = json_decode($params, true);
        $Mail = QUI::getMailManager()->getPHPMailer();

        $Mail->Mailer = 'mail';

        if (!empty($params['MAILFrom'])) {
            $Mail->From = $params['MAILFrom'];
        }

        if (!empty($params['MAILFromText'])) {
            $Mail->FromName = $params['MAILFromText'];
        }

        if (!empty($params['MAILReplyTo'])) {
            $Mail->addReplyTo($params['MAILReplyTo']);
        }

        if (!empty($params['SMTPServer'])) {
            $Mail->Mailer = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Host = $params['SMTPServer'];
        }

        if (!empty($params['SMTPUser'])) {
            $Mail->Mailer = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Username = $params['SMTPUser'];
        }

        if (!empty($params['SMTPPass'])) {
            $Mail->Mailer = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Password = $params['SMTPPass'];
        }

        if (!empty($params['SMTPPort'])) {
            $Mail->Mailer = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Port = (int)$params['SMTPPort'];
        }

        if (!empty($params['SMTPSecure'])) {
            switch ($params['SMTPSecure']) {
                case "ssl":
                    $Mail->SMTPSecure = $params['SMTPSecure'];

                    $Mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer' => (int)$params['SMTPSecureSSL_verify_peer'],
                            'verify_peer_name' => (int)$params['SMTPSecureSSL_verify_peer_name'],
                            'allow_self_signed' => (int)$params['SMTPSecureSSL_allow_self_signed']
                        ]
                    ];
                    break;
                case "tls":
                    $Mail->SMTPSecure = $params['SMTPSecure'];
                    break;
            }
        }

        // debug output
        try {
            $mailRecipient = QUI::conf('mail', 'admin_mail');

            if (!empty($params['adminMail'])) {
                $mailRecipient = $params['adminMail'];
            }

            $recipients = $mailRecipient;
            $recipients = trim($recipients);
            $recipients = explode(',', $recipients);

            foreach ($recipients as $recipient) {
                $Mail->addAddress($recipient);
            }

            $Mail->Subject = QUI::getLocale()->get('quiqqer/core', 'text.mail.subject');
            $Mail->Body = QUI::getLocale()->get('quiqqer/core', 'text.mail.body');

            $Mail->SMTPDebug = 3;
            $Mail->Debugoutput = static function ($str, $level): void {
                QUI\System\Log::writeRecursive(rtrim($str) . PHP_EOL);
                QUI\Mail\Log::write(rtrim($str));
            };

            QUI\Mail\Log::logSend($Mail);
            $Mail->send();
            QUI\Mail\Log::logDone($Mail);
        } catch (Exception $Exception) {
            QUI\Mail\Log::logException($Exception);

            throw new QUI\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        // send mail with Mail Template
        try {
            $Mailers = QUI::getMailManager()->getMailer();
            $Mailers->setSubject(QUI::getLocale()->get('quiqqer/core', 'text.mail.subject'));
            $Mailers->setBody(QUI::getLocale()->get('quiqqer/core', 'text.mail.body'));
            $Mailers->addRecipient($mailRecipient);
            $Mailers->send();
        } catch (Exception $Exception) {
            QUI\Mail\Log::logException($Exception);
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/core', 'message.testmail.success')
        );
    },
    ['params'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
