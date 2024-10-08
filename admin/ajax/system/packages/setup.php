<?php

/**
 * Install a wanted package or package list
 *
 * @param string|array $packages - Name of the package
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_setup',
    static function ($package): void {
        QUI::getPackageManager()->setup($package);
    },
    ['package'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
