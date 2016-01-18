<?php

/**
 * test mail settings
 */
QUI::$Ajax->registerFunction(
    'ajax_system_mailTest',
    function ($params) {
        $params = json_decode($params, true);
        $Mail   = QUI::getMailManager()->getPHPMailer();

        if (isset($params['SMTPServer'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Host     = $params['SMTPServer'];
        }

        if (isset($params['SMTPUser'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Username = $params['SMTPUser'];
        }

        if (isset($params['SMTPPass'])) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Password = $params['SMTPPass'];
        }

        if (isset($config['SMTPPort'])
            && !empty($params['SMTPPort'])
        ) {
            $Mail->Mailer   = 'smtp';
            $Mail->SMTPAuth = true;
            $Mail->Port     = (int)$params['SMTPPort'];
        }

        // debug output
        QUI\System\Log::writeRecursive($params);

        try {
            $Mail->addAddress(QUI::conf('mail', 'admin_mail'));

            $Mail->Subject = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.subject'
            );

            $Mail->Body = QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'text.mail.body'
            );

            ob_start();

            $Mail->SMTPDebug = 2;
            $Mail->send();

            $debugOutput = ob_get_contents();
            ob_end_clean();

            // debug output
            QUI\System\Log::writeRecursive($debugOutput);

        } catch (\Exception $Exception) {
            throw new QUI\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        QUI::getMessagesHandler()->addSuccess(
            'Mail wurde erfolgreich versendet'
        );
    },
    array('params'),
    'Permission::checkUser'
);
