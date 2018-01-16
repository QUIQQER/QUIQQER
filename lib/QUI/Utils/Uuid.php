<?php

/**
 * This file contains \QUI\Utils\Uuid
 */

namespace QUI\Utils;

use QUI;

/**
 * Class Uuid
 * - Helps to generate unique IDs
 *
 * @package QUI\Utils
 */
class Uuid
{
    /**
     * Return a unique id
     */
    public static function get()
    {
        try {
            $UUID = \Ramsey\Uuid\Uuid::uuid1();
        } catch (\Exception $Exception) {
            $UUID = \Ramsey\Uuid\Uuid::uuid3(
                \Ramsey\Uuid\Uuid::NAMESPACE_DNS,
                microtime(true).uniqid()
            );
        }

        return $UUID->toString();
    }
}
