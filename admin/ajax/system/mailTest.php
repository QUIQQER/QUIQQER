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

            $DebugOutput        = new stdClass();
            $DebugOutput->debug = "\n";

            $Mail->SMTPDebug   = 2;
            $Mail->Debugoutput = function ($str, $level) use ($DebugOutput) {
                $DebugOutput->debug .= rtrim($str) . "\n";
            };

            $Mail->send();

            QUI\System\Log::addInfo(
                $DebugOutput->debug,
                $params,
                'mailtest'
            );

        } catch (\Exception $Exception) {
            throw new QUI\Exception(
                $Exception->getMessage(),
                $Exception->getCode()
            );
        }

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
                'message.testmail.success'
            )
        );
    },
    array('params'),
    'Permission::checkUser'
);
