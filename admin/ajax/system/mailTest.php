<?php

/**
 * test mail settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_mailTest',
    function ($params) {
        $params = \json_decode($params, true);
        $Mail   = QUI::getMailManager()->getPHPMailer();

        $Mail->Mailer = 'mail';

        if (isset($params['SMTPServer']) && !empty($params['SMTPServer'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Host     = $params['SMTPServer'];
        }

        if (isset($params['SMTPUser']) && !empty($params['SMTPUser'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Username = $params['SMTPUser'];
        }

        if (isset($params['SMTPPass']) && !empty($params['SMTPPass'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Password = $params['SMTPPass'];
        }

        if (isset($params['SMTPPort']) && !empty($params['SMTPPort'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Port     = (int)$params['SMTPPort'];
        }

        if (isset($params['SMTPSecure']) && !empty($params['SMTPSecure'])) {
            switch ($params['SMTPSecure']) {
                case "ssl":
                    $Mail->SMTPSecure = $params['SMTPSecure'];

                    $Mail->SMTPOptions = [
                        'ssl' => [
                            'verify_peer'       => (int)$params['SMTPSecureSSL_verify_peer'],
                            'verify_peer_name'  => (int)$params['SMTPSecureSSL_verify_peer_name'],
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
            $recipients = QUI::conf('mail', 'admin_mail');
            $recipients = \trim($recipients);
            $recipients = \explode(',', $recipients);

            foreach ($recipients as $recipient) {
                $Mail->addAddress($recipient);
            }

            $Mail->Subject = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.subject'
            );

            $Mail->Body = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.body'
            );

            $Mail->SMTPDebug   = 3;
            $Mail->Debugoutput = function ($str, $level) {
                QUI\System\Log::writeRecursive(\rtrim($str).PHP_EOL);
                QUI\Mail\Log::write(\rtrim($str));
            };

            QUI\Mail\Log::logSend($Mail);
            $Mail->send();
            QUI\Mail\Log::logDone($Mail);
        } catch (\Exception $Exception) {
            QUI\Mail\Log::logException($Exception);

            throw new QUI\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        // send mail with Mail Template
        try {
            $Mailers = QUI::getMailManager()->getMailer();
            $Mailers->setSubject(QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.subject'
            ));

            $Mailers->setBody(QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.body'
            ));

            $Mailers->addRecipient(QUI::conf('mail', 'admin_mail'));
            $Mailers->send();
        } catch (\Exception $Exception) {
            QUI\Mail\Log::logException($Exception);
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get('quiqqer/quiqqer', 'message.testmail.success')
        );
    },
    ['params'],
    'Permission::checkSU'
);
