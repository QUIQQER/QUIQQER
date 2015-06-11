<?php

/**
 * cache clearing
 *
 * @param array $params
 */
function ajax_system_cache_clear($params)
{
    $params = json_decode($params, true);

    if (isset($params['compile']) && $params['compile'] == 1) {
        QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
    }

    if (isset($params['plugins']) && $params['plugins'] == 1) {
        QUI\Plugins\Manager::clearCache();
    }

    if (isset($params['complete']) || $params['complete'] == 1) {
        QUI\Cache\Manager::clearAll();
        QUI\Plugins\Manager::clearCache();
    }
}

QUI::$Ajax->register(
    'ajax_system_cache_clear',
    array('params'),
    'Permission::checkSU'
);
