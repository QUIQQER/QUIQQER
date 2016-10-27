<?php

/**
 * Return package lock
 *
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_getLock',
    function ($package) {
        return QUI::getPackageManager()
            ->getInstalledPackage($package)
            ->getLock();
    },
    array('package'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
