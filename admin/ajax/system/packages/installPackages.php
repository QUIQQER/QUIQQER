<?php

/**
 * Install a wanted package
 * - used via the store
 *
 * @param string|array $packages - Name of the package
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_installPackages',
    function ($packageName, $packageVersion, $server) {
        $Packages = QUI::getPackageManager();
        $server   = json_decode($server, true);

        if ($server && is_array($server)) {
            foreach ($server as $s) {
                $Packages->addServer($s['server'], array(
                    'type' => $s['type']
                ));
            }
        }

        $Packages->install($packageName, $packageVersion);
    },
    array('packageName', 'packageVersion', 'server'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
