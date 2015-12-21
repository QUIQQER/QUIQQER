<?php

/**
 * Set a version to packages
 *
 * @param string $packages - JSON Array, list of packages
 * @param string $version - Wanted version
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_setVersion',
    function ($packages, $version) {
        QUI::getPackageManager()->setPackage(
            json_decode($packages, true),
            $version
        );
    },
    array('packages', 'version'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
