<?php

/**
 * Install a wanted package
 *
 * @param String $package - Name of the package
 */
function ajax_system_packages_install($package)
{
    \QUI::getPackageManager()->install( $package );
}

\QUI::$Ajax->register(
    'ajax_system_packages_install',
    array( 'package' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
