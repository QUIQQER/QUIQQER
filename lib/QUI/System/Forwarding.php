<?php

namespace QUI\System;

use QUI;
use DusanKasan\Knapsack\Collection;

/**
 * Class Forwarding
 *
 * @package QUI\System
 */
class Forwarding
{
    /**
     * Create a forwarding entry
     *
     * @param string $from
     * @param string $to
     * @param string|int $httpCode
     */
    public static function create($from, $to, $httpCode)
    {
        self::getConfg()->setValue($from, $to, $httpCode);
    }

    /**
     * Update a forwarding entry
     *
     * @param string $from
     * @param string $to
     * @param string |int $httpCode
     */
    public static function update($from, $to, $httpCode)
    {
        self::getConfg()->setValue($from, $to, $httpCode);
    }

    /**
     * Löscht ein forwarding eintrag
     *
     * @param string $from
     */
    public static function delete($from)
    {
        self::getConfg()->del($from);
    }

    /**
     * Return the forwarding config
     *
     * @return QUI\Config
     */
    public static function getConfg()
    {
        if (!file_exists(CMS_DIR . 'etc/forwarding.ini.php')) {
            file_put_contents(CMS_DIR . 'etc/forwarding.ini.php', '');
        }

        return QUI::getConfig('etc/forwarding.ini.php');
    }

    /**
     * Return the list
     *
     * @return Collection
     */
    public static function getList()
    {
        return new Collection(self::getConfg()->toArray());
    }
}
