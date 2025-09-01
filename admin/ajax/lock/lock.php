<?php

/**
 * unlock
 *
 * @param string $package
 * @param string $key
 */

QUI::$Ajax->registerFunction(
    'ajax_lock_lock',
    static function ($package, $key): void {
        if (empty($package)) {
            $package = 'quiqqer/core';
        }

        $Package = QUI::getPackage($package);
        QUI\Lock\Locker::lock($Package, $key);
    },
    ['package', 'key'],
    'Permission::checkAdminUser'
);
