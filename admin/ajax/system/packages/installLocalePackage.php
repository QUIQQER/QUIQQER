<?php

/**
 * Install a wanted package or package list with local repository
 *
 * @param string|array $packages - Name of the package
 */
function ajax_system_packages_installLocalePackage($packages)
{
    $json = json_decode($packages, true);

    if ($json && is_array($json)) {
        foreach ($json as $pkg => $version) {
            QUI::getPackageManager()->installLocalPackage($pkg, $version);
        }

        return;
    }

    QUI::getPackageManager()->installLocalPackage($packages);
}

QUI::$Ajax->register(
    'ajax_system_packages_installLocalePackage',
    array('packages'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
