<?php

/**
 * cache purging
 */
QUI::$Ajax->registerFunction(
    'ajax_system_cache_purge',
    function () {
        QUI\Cache\Manager::purge();
    },
    false,
    'Permission::checkSU'
);
