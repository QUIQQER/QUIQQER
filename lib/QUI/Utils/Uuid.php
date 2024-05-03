<?php

/**
 * This file contains \QUI\Utils\Uuid
 */

namespace QUI\Utils;

use function method_exists;
use function microtime;
use function uniqid;

/**
 * Class Uuid
 * - Helps to generate unique IDs
 */
class Uuid
{
    /**
     * Return a unique id
     */
    public static function get(): string
    {
        try {
            $UUID = \Ramsey\Uuid\Uuid::uuid1();
        } catch (\Exception) {
            $UUID = \Ramsey\Uuid\Uuid::uuid3(
                \Ramsey\Uuid\Uuid::NAMESPACE_DNS,
                microtime(true) . uniqid()
            );
        }

        if (method_exists($UUID, 'toString')) {
            return $UUID->toString();
        }

        return (string)$UUID;
    }
}
