<?php

/**
 * This file contains QUI\Security\RequestOrigin
 */

namespace QUI\Security;

use QUI\Exception;

use function in_array;
use function is_int;
use function is_string;
use function parse_url;
use function strtolower;
use function trim;

/**
 * Validates browser-provided request origin metadata.
 */
final class RequestOrigin
{
    /**
     * Reject a request when the available browser metadata proves that it
     * originated outside the current origin.
     *
     * Missing metadata remains accepted for compatibility with non-browser
     * clients and browsers which do not send Fetch Metadata headers.
     *
     * @throws Exception
     */
    public static function assertNotCrossOrigin(): void
    {
        $fetchSite = $_SERVER['HTTP_SEC_FETCH_SITE'] ?? null;

        if (is_string($fetchSite)) {
            $fetchSite = strtolower(trim($fetchSite));

            if (
                $fetchSite !== ''
                && !in_array($fetchSite, ['same-origin', 'none'], true)
            ) {
                self::reject();
            }
        }

        $source = $_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? null;

        if (!is_string($source) || trim($source) === '') {
            return;
        }

        $requestHost = $_SERVER['HTTP_HOST'] ?? null;

        if (!is_string($requestHost) || trim($requestHost) === '') {
            self::reject();
        }

        $sourceHost = parse_url($source, PHP_URL_HOST);
        $sourcePort = parse_url($source, PHP_URL_PORT);
        $targetHost = parse_url('http://' . $requestHost, PHP_URL_HOST);
        $targetPort = parse_url('http://' . $requestHost, PHP_URL_PORT);

        if (
            !is_string($sourceHost)
            || !is_string($targetHost)
            || strtolower($sourceHost) !== strtolower($targetHost)
            || self::normalizePort($sourcePort) !== self::normalizePort($targetPort)
        ) {
            self::reject();
        }
    }

    private static function normalizePort(int|false|null $port): ?int
    {
        return is_int($port) ? $port : null;
    }

    /**
     * @throws Exception
     */
    private static function reject(): never
    {
        throw new Exception('Invalid request origin.', 403);
    }
}
