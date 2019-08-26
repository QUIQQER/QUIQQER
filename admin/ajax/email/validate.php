<?php

use QUI\Utils\Security\Orthos;

/**
 * Check if an email address is correct (syntax)
 *
 * @param string $mail - email address
 * @return Bool
 */
QUI::$Ajax->registerFunction(
    'ajax_email_validate',
    function ($mail) {
        return boolval(Orthos::checkMailSyntax($mail));
    },
    array('mail'),
    'Permission::checkUser'
);
