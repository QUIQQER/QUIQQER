<?php

/**
 * Return all installed packages
 *
 * @return Array
 */
function ajax_system_packages_get($package)
{
    return \QUI::getPackageManager()->getPackage( $package );
}

QUI::$Ajax->register(
	'ajax_system_packages_get',
    array( 'package' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>