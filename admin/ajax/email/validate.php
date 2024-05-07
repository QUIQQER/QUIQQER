<?php

/**
 * Check if an email address is correct (syntax)
 *
 * @param string $mail - email address
 * @return Bool
 */

use QUI\Utils\Security\Orthos;

QUI::$Ajax->registerFunction(
    'ajax_email_validate',
    fn($mail) => (bool) Orthos::checkMailSyntax($mail),
    ['mail'],
    'Permission::checkUser'
);
