<?php

/**
 *
 */
function ajax_settings_window($file)
{
    if ( !file_exists( $file ) ) {
        return array();
    }

    $Window = \QUI\Utils\DOM::parseDomToWindow(
        \QUI\Utils\XML::getDomFromXml( $file )
    );

    if ( !$Window ) {
        return array();
    }

    return $Window->toArray();
}

\QUI::$Ajax->register(
    'ajax_settings_window',
    array( 'file' ),
    'Permission::checkAdminUser'
);
