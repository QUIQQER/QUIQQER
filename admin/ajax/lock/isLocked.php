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
        $Package = QUI::getPackage($package);

        return QUI\Lock\Locker::isLocked($Package, $key);
    },
    ['package', 'key'],
    'Permission::checkAdminUser'
);
