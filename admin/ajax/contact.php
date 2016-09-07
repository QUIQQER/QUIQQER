<?php

/**
 * Default contact mail
 *
 * @param string $email
 * @param string $name
 * @param string $message
 *
 * @return bool
 * @throws \QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_contact',
    function ($email, $name, $message) {
        if (empty($email) || empty($name) || empty($message)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.contact.params.empty'
                )
            );
        }

        if (!QUI\Utils\Security\Orthos::checkMailSyntax($email)) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.contact.wrong.email'
                )
            );
        }


        $body
            = "

From: {$name}
E-Mail: {$email}

Message: {$message}

";

        try {
            QUI::getMailManager()->send(
                QUI::conf('mail', 'admin_mail'),
                'Contact',
                $body
            );
        } catch (QUI\Exception $Exception) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/system',
                    'exception.contact.send.mail'
                )
            );
        }

        return true;
    },
    array('email', 'name', 'message')
);
