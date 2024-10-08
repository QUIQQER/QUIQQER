<?php

/**
 * Set a version to packages
 *
 * @param string $packages - JSON Array, list of packages
 * @param string $version - Wanted version
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_setVersion',
    static function ($packages, $version): void {
        QUI::getPackageManager()->setPackageVersion(
            json_decode($packages, true),
            $version
        );
    },
    ['packages', 'version'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
