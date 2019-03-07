<?php

/**
 * Check for updates
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_update_getOutdated',
    function ($force) {
        if (!isset($force)) {
            $force = false;
        }

        if (is_int($force) || is_string($force)) {
            $force = (int)$force;
            $force = $force ? true : false;
        }

        return QUI::getPackageManager()->getOutdated($force);
    },
    ['force'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
