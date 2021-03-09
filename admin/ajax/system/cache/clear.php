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

        if (!empty($params['compile'])) {
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
        }

        if (!empty($params['templates']) || !empty($params['quiqqer-template'])) {
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/templates');
            QUI\Utils\System\File::unlink(VAR_DIR.'cache/compile');
        }


        if (!empty($params['complete'])) {
            QUI\Cache\Manager::clearAll();
        }


        if (!empty($params['settings'])) {
            QUI\Cache\Manager::clearSettingsCache();
        }

        // quiqqer internal cache
        if (!empty($params['quiqqer'])) {
            QUI\Cache\Manager::clearCompleteQuiqqerCache();
        }

        if (!empty($params['quiqqer-projects'])) {
            QUI\Cache\Manager::clearProjectsCache();
        }

        if (!empty($params['quiqqer-groups'])) {
            QUI\Cache\Manager::clearGroupsCache();
        }

        if (!empty($params['quiqqer-users'])) {
            QUI\Cache\Manager::clearUsersCache();
        }

        if (!empty($params['quiqqer-permissions'])) {
            QUI\Cache\Manager::clearPermissionsCache();
        }

        if (!empty($params['quiqqer-users-groups'])) {
            QUI\Cache\Manager::clearGroupsCache();
            QUI\Cache\Manager::clearUsersCache();
            QUI\Cache\Manager::clearPermissionsCache();
        }

        if (!empty($params['quiqqer-packages'])) {
            QUI\Cache\Manager::clearPackagesCache();
        }

        if (!empty($params['longterm'])) {
            QUI\Cache\LongTermCache::clear();
        }
    },
    ['params'],
    'Permission::checkSU'
);
