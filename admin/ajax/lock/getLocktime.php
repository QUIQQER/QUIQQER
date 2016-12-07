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
        return QUI\Lock\Locker::getLockTime($package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
