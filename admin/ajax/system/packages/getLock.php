<?php

/**
 * Return package lock
 *
 * @param string $params
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_getLock',
    static function ($package): array {
        return QUI::getPackageManager()
            ->getInstalledPackage($package)
            ->getLock();
    },
    ['package'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
