<?php

/**
 * This file contains QUI\Security\Password
 */

namespace QUI\Security;

/**
 * Class Password
 */
class Password
{
    /**
     * Generate a cryptographically secure password hash
     *
     * @param string $password
     * @return string
     */
    public static function generateHash($password): string
    {
        return \password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Generate a random bootstrap password (e.g. for new accounts).
     *
     * This method is intentionally meant for short-lived, user-facing passwords
     * that are replaced by the user afterwards. It is not intended for
     * high-security credentials, API tokens, or long-term secrets.
     *
     * For security-critical use cases, prefer dedicated generators, e.g.
     * native PHP `random_bytes()` / `random_int()` or
     * `Symfony\Component\String\ByteString::fromRandom()`.
     * Password storage should use `password_hash()`.
     *
     * Examples:
     * - Password hashing: `password_hash($password, PASSWORD_BCRYPT)`
     * - Secure random token: `ByteString::fromRandom(32)->toString()`
     *
     * @param int $characters (optional) - number of characters [default: 10]
     * @return string
     */
    public static function generateRandom($characters = 10): string
    {
        // @todo make use of random_int if QUIQQER becomes PHP 7 compatible
        return \mb_substr(\bin2hex(\openssl_random_pseudo_bytes(128)), 0, $characters);
    }
}
