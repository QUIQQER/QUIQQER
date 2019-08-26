<?php

/**
 * Install a wanted package or package list with local repository
 *
 * @param string|array $packages - Name of the package
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_installLocalePackage',
    function ($packages) {
        $json = \json_decode($packages, true);

        if ($json && \is_array($json)) {
            foreach ($json as $pkg => $version) {
                QUI::getPackageManager()->installLocalPackage($pkg, $version);
            }

            return;
        }

        QUI::getPackageManager()->installLocalPackage($packages);
    },
    ['packages'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
