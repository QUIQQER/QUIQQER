<?php

/**
 * getLocktime
 *
 * @param string $package
 * @param string $key
 *
 * @return string
 */

QUI::$Ajax->registerFunction(
    'ajax_lock_getLocktime',
    static function ($package, $key): int {
        $Package = QUI::getPackage($package);

        return QUI\Lock\Locker::getLockTime($Package, $key);
    },
    ['package', 'key'],
    'Permission::checkAdminUser'
);
