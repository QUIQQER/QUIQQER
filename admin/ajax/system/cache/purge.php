<?php

/**
 * cache purging
 */

QUI::getAjax()->registerFunction(
    'ajax_system_cache_purge',
    static function (): void {
        QUI\Cache\Manager::purge();
    },
    false,
    'Permission::checkSU'
);
