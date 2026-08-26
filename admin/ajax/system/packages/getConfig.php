<?php

/**
 * This file contains ajax_system_packages_getConfig
 */

/**
 * Return the config of a package
 *
 * @param string $package - Name of the package
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_system_packages_getConfig',
    static function ($package) {
        $Package = QUI::getPackageManager()->getInstalledPackage($package);
        $Config = $Package->getConfig();

        if ($Config === null) {
            return [];
        }

        return $Config->toArray();
    },
    ['package'],
    [
        'Permission::checkAdminUser'
    ]
);
