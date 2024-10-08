<?php

/**
 * Check for updates
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_update_getOutdated',
    static function ($force): array {
        if (!isset($force)) {
            $force = false;
        }

        if (is_int($force) || is_string($force)) {
            $force = (int)$force;
            $force = (bool)$force;
        }

        return QUI::getPackageManager()->getOutdated($force);
    },
    ['force'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
