<?php

/**
 * cache clearing
 *
 * @param array $params
 */
QUI::$Ajax->registerFunction(
    'ajax_system_cache_clear',
    function ($params) {
        $params = \json_decode($params, true);

        if (isset($params['compile']) && $params['compile'] == 1) {
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
        }

        if (isset($params['plugins']) && $params['plugins'] == 1) {
            QUI\Plugins\Manager::clearCache();
        }

        if (isset($params['templates']) && $params['templates'] == 1) {
            // @TODO: Ist das die richtige Funktion zum Löschen des Template Caches?
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/templates');
        }

        if (isset($params['complete']) && $params['complete'] == 1) {
            QUI\Cache\Manager::clearAll();
            QUI\Plugins\Manager::clearCache();
        }
    },
    ['params'],
    'Permission::checkSU'
);
