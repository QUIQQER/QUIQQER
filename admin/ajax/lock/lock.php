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
        QUI\Lock\Locker::lock($package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
