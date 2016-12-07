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
        QUI\Lock\Locker::unlock($package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
