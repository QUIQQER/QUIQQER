<?php

/**
 * Update a package or the entire system
 */
function ajax_system_update($package)
{
    QUI::getPackageManager()->update( $package );
}

QUI::$Ajax->register(
	'ajax_system_update',
    array( 'package' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>