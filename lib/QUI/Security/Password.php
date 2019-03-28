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
        return \password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Generate a new, random password
     *
     * @param int $characters (optional) - number of characters [default: 10]
     * @return string
     */
    public static function generateRandom($characters = 10)
    {
        // @todo make use of random_int if QUIQQER becomes PHP 7 compatible
        return \mb_substr(\bin2hex(\openssl_random_pseudo_bytes(128)), 0, $characters);
    }
}
