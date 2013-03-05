<?php

/**
 * Return all installed packages
 *
 * @return Array
 */
function ajax_system_packages_search($str)
{
    $result = QUI::getPackageManager()->searchPackage( $str );
    $result = \Utils_Grid::getResult( $result, 1, 20 );

    $data = array();

    $list      = QUI::getPackageManager()->getInstalled();
    $installed = array();

    foreach ( $list as $package ) {
        $installed[ $package['name'] ] = true;
    }


    foreach ( $result['data'] as $package => $description )
    {
        $data[] = array(
            'package'     => $package,
            'description' => $description,
            'isInstalled' => isset( $installed[ $package ] ) ? true : false
        );
    }

    $result['data'] = $data;

    return $result;
}

QUI::$Ajax->register(
	'ajax_system_packages_search',
    array( 'str' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>