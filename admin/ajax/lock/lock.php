<?php

/**
 * unlock
 *
 * @param string $package
 * @param string $key
 */
QUI::$Ajax->registerFunction(
    'ajax_lock_lock',
    function ($package, $key) {
        $Package = QUI::getPackage($package);
        QUI\Lock\Locker::lock($Package, $key);
    },
    ['package', 'key'],
    'Permission::checkAdminUser'
);
