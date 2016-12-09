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
    function ($package, $key) {
        $Package = QUI::getPackage($package);
        return QUI\Lock\Locker::getLockTime($Package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
