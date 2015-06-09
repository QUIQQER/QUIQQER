<?php

/**
 * Install a wanted package or package list
 *
 * @param string|array $package - Name of the package
 */
function ajax_system_packages_install($package)
{
    if (is_array($package)) {
        foreach ($package as $pkg => $version) {
            QUI::getPackageManager()->install($pkg, $version);
        }

        return;
    }

    QUI::getPackageManager()->install($package);
}

QUI::$Ajax->register(
    'ajax_system_packages_install',
    array('package'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
