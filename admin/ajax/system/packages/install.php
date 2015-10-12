<?php

/**
 * Install a wanted package or package list
 *
 * @param string|array $packages - Name of the package
 */
function ajax_system_packages_install($packages)
{
    $json = json_decode($packages, true);

    if ($json && is_array($json)) {
        foreach ($json as $pkg => $version) {
            QUI::getPackageManager()->install($pkg, $version);
        }

        return;
    }

    QUI::getPackageManager()->install($packages);
}

QUI::$Ajax->register(
    'ajax_system_packages_install',
    array('packages'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
