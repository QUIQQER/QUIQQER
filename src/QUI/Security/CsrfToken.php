<?php

/**
 * This file contains QUI\Security\CsrfToken
 */

namespace QUI\Security;

use QUI;
use QUI\Exception;

use function bin2hex;
use function hash_equals;
use function is_string;
use function random_bytes;

/**
 * Session-bound token for requests that require explicit user intent.
 */
final class CsrfToken
{
    public const SESSION_KEY = 'quiqqer.security.csrfToken';

    public static function get(): string
    {
        $token = QUI::getSession()->get(self::SESSION_KEY);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $token = bin2hex(random_bytes(32));
        QUI::getSession()->set(self::SESSION_KEY, $token);

        return $token;
    }

    /**
     * @throws Exception
     */
    public static function assertValid(mixed $token): void
    {
        if (!is_string($token) || !hash_equals(self::get(), $token)) {
            throw new Exception('Invalid request token.', 403);
        }
    }
}
