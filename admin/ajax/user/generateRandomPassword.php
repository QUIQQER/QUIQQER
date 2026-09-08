<?php

/**
 * Generate a cryptographically secure bootstrap password for user administration.
 *
 * @return string
 */

use QUI\Security\Password;

QUI::getAjax()->registerFunction(
    'ajax_user_generateRandomPassword',
    static fn(): string => Password::generateRandom(),
    [],
    ['Permission::checkAdminUser', 'quiqqer.admin.users.edit']
);
