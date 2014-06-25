<?php

/**
 * Return config params from a xml file
 *
 * @param String $file
 * @return Array
 */

function ajax_settings_get($file)
{
    if ( !file_exists( $file ) ) {
        return array();
    }

    $Config = \QUI\Utils\XML::getConfigFromXml( $file );

    if ( !$Config ) {
        return array();
    }

    return $Config->toArray();
}

\QUI::$Ajax->register(
    'ajax_settings_get',
    array( 'file' ),
    'Permission::checkAdminUser'
);
