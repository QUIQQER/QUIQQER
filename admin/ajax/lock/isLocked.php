<?php

/**
 * isLocked
 *
 * @param string $package
 * @param string $key
 *
 * @return bool
 */

QUI::$Ajax->registerFunction(
    'ajax_lock_isLocked',
    static function ($package, $key) {
        if (empty($package)) {
            $package = 'quiqqer/core';
        }

        $Package = QUI::getPackage($package);
        return QUI\Lock\Locker::isLocked($Package, $key);
    },
    ['package', 'key'],
    'Permission::checkAdminUser'
);
