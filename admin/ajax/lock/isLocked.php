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
    function ($package, $key) {
        return QUI\Lock\Locker::isLocked($package, $key);
    },
    array('package', 'key'),
    'Permission::checkAdminUser'
);
