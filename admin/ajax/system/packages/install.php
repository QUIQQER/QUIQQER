<?php

/**
 * Install a wanted package
 */
function ajax_system_packages_install($package)
{
    return QUI::getPackageManager()->install( $package );
}

QUI::$Ajax->register(
    'ajax_system_packages_install',
    array( 'package' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>