<?php

/**
 * Install a wanted package or package list
 *
 * @param string|array $packages - Name of the package
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_install',
    function ($packages) {
        QUI::getPackageManager()->install(json_decode($packages, true));
    },
    array('packages'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
