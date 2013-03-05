<?php

/**
 * cache purging
 */
function ajax_system_cache_purge()
{
    System_Cache_Manager::purge();
}
QUI::$Ajax->register('ajax_system_cache_purge', false, 'Permission::checkSU');

?>