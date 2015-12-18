<?php

/**
 * Return the composer data of the package
 *
 * @param string $package - Name of the package
 * @return array
 */
function ajax_system_packages_get($package)
{
    return QUI::getPackageManager()->getInstalledPackage($package)->getComposerData();
}

QUI::$Ajax->register(
    'ajax_system_packages_get',
    array('package'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
