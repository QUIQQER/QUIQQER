<?php

/**
 * Set a version to packages
 *
 * @param string $packages - JSON Array, list of packages
 * @param string $version - Wanted version
 */
function ajax_system_packages_setVersion($packages, $version)
{
    QUI::getPackageManager()->setPackage(
        json_decode($packages, true),
        $version
    );
}

QUI::$Ajax->register(
    'ajax_system_packages_setVersion',
    array('packages', 'version'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
