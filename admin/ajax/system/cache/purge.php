<?php

/**
 * cache purging
 */
function ajax_system_cache_purge()
{
    \QUI\Cache\Manager::purge();
}

\QUI::$Ajax->register('ajax_system_cache_purge', false, 'Permission::checkSU');
