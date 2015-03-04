<?php

/**
 * Return all installed packages
 *
 * @param String $str - Search string
 * @param String|Integer $from - Sheet start
 * @param String|Integer $max - Limit of the results
 * @return Array
 */
function ajax_system_packages_search($str, $from, $max)
{
    if ( !isset( $from ) || !$from ) {
        $from = 1;
    }

    if ( !isset( $max ) || !$max ) {
        $max = 20;
    }

    $from = (int)$from;
    $max  = (int)$max;

    $result = \QUI::getPackageManager()->searchPackage( $str );
    $result = \QUI\Utils\Grid::getResult( $result, $from, $max );

    $data = array();

    $list      = \QUI::getPackageManager()->getInstalled();
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

\QUI::$Ajax->register(
    'ajax_system_packages_search',
    array( 'str', 'from', 'max' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
