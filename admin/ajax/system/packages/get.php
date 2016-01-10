<?php

/**
 * Return the composer data of the package
 *
 * @param string $package - Name of the package
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_get',
    function ($package) {
        return QUI::getPackageManager()->getInstalledPackage($package)->getComposerData();
    },
    array('package'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
