<?php

/**
 * This file contains QUI\Workspace\Search\Search
 */
namespace QUI\Workspace\Search;

use QUI;

/**
 * Class Cron
 *
 * @package QUI\Workspace
 */
class Cron
{
    /**
     * Build the search cache
     *
     * @return void
     */
    public static function buildSearchCache()
    {
        Builder::getInstance()->setup();
    }
}
