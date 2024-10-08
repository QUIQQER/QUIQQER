<?php

/**
 * Install a wanted package or package list
 *
 * @param string|array $packages - Name of the package
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_install',
    static function ($packages): void {
        QUI::getPackageManager()->install(\json_decode($packages, true));
    },
    ['packages'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
