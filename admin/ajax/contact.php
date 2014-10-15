<?php

/**
 * Default contact mail
 */

function ajax_contact($email, $name, $message)
{
    if ( empty( $email ) || empty( $name ) || empty( $message ) )
    {
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.contact.params.empty'
            )
        );
    }

    if ( !\QUI\Utils\Security\Orthos::checkMailSyntax( $email ) )
    {
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.contact.wrong.email'
            )
        );
    }


    $body = "

From: {$name}
E-Mail: {$email}

Message: {$message}

";

    try
    {
        \QUI::getMailManager()->send(
            \QUI::conf( 'mail', 'admin_mail' ),
            'Contact',
            $body
        );

    } catch ( \QUI\Exception $Exception )
    {
        throw new \QUI\Exception(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.contact.send.mail'
            )
        );
    }

    return true;
}

\QUI::$Ajax->register(
    'ajax_contact',
    array( 'email', 'name', 'message' )
);
