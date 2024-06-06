<?php

/**
 * cache purging
 */

QUI::$Ajax->registerFunction(
    'ajax_system_cache_purge',
    static function (): void {
        QUI\Cache\Manager::purge();
    },
    false,
    'Permission::checkSU'
);
