<?php

/**
 * unlock
 *
 * @param string $package
 * @param string $key
 */
QUI::$Ajax->registerFunction(
    'ajax_lock_unlock',
    function ($package, $key) {
        $Package = QUI::getPackage($package);
        QUI\Lock\Locker::unlock($Package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
