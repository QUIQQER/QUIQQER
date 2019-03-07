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
QUI::$Ajax->registerFunction(
    'ajax_system_packages_getConfig',
    function ($package) {
        $Package = QUI::getPackageManager()->getInstalledPackage($package);

        return $Package->getConfig()->toArray();
    },
    ['package'],
    [
        'Permission::checkAdminUser'
    ]
);
