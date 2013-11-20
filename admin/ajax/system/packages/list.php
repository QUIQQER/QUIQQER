<?php

/**
 * Return all installed packages
 *
 * @return Array
 */
function ajax_system_packages_list($params)
{
    return \QUI::getPackageManager()->getInstalled(
        json_decode( $params, true )
    );
}

QUI::$Ajax->register(
	'ajax_system_packages_list',
    array( 'params' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>