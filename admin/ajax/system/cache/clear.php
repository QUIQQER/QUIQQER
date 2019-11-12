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

        if (isset($params['templates']) && $params['templates'] == 1) {
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/templates');
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
        }


        if (isset($params['complete']) && $params['complete'] == 1) {
            QUI\Cache\Manager::clearAll();
        }


        if (isset($params['settings']) && $params['settings'] == 1) {
            QUI\Cache\Manager::clearSettingsCache();
        }

        // quiqqer internal cache
        if (isset($params['quiqqer']) && $params['quiqqer'] == 1) {
            QUI\Cache\Manager::clearCompleteQuiqqerCache();
        }

        if (isset($params['quiqqer-projects']) && $params['quiqqer-projects'] == 1) {
            QUI\Cache\Manager::clearProjectsCache();
        }

        if (isset($params['quiqqer-groups']) && $params['quiqqer-groups'] == 1) {
            QUI\Cache\Manager::clearGroupsCache();
        }

        if (isset($params['quiqqer-users']) && $params['quiqqer-users'] == 1) {
            QUI\Cache\Manager::clearUsersCache();
        }

        if (isset($params['quiqqer-permissions']) && $params['quiqqer-permissions'] == 1) {
            QUI\Cache\Manager::clearPermissionsCache();
        }

        if (isset($params['quiqqer-packages']) && $params['quiqqer-packages'] == 1) {
            QUI\Cache\Manager::clearPackagesCache();
        }
    },
    ['params'],
    'Permission::checkSU'
);
