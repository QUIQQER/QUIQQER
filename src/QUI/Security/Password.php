<?php

/**
 * This file contains QUI\Security\Password
 */

namespace QUI\Security;

use function password_hash;
use function random_int;
use function strlen;

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
    public static function generateHash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Generate a random bootstrap password (e.g. for new accounts).
     *
     * This method is intentionally meant for short-lived, user-facing passwords
     * that are replaced by the user afterward. It is not intended for
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
     * @param int $length (optional) - number of characters [default: 16]
     * @return string
     */
    public static function generateRandom(int $length = 16): string
    {
        if ($length <= 0) {
            return '';
        }

        $charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-_';
        $charsetLength = strlen($charset);
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $charset[random_int(0, $charsetLength - 1)];
        }

        return $password;
    }
}
