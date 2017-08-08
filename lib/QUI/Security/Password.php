<?php

/**
 * This file contains QUI\Security\Password
 */

namespace QUI\Security;

use QUI;

/**
 * Class Password
 * @package QUI
 */
class Password
{
    /**
     * Generate a cryptographically secure password hash
     *
     * @param string $password
     * @return string
     */
    public static function generateHash($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }
}
