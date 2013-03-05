<?php

/**
 *
 */
function ajax_settings_window($file)
{
    $file = SYS_DIR . $file;

    if ( !file_exists( $file ) ) {
        return array();
    }

    $Window = Utils_Dom::parseDomToWindow(
        Utils_Xml::getDomFromXml( $file )
    );

    if ( !$Window ) {
        return array();
    }

    return $Window->toArray();
}
QUI::$Ajax->register( 'ajax_settings_window', array( 'file' ), 'Permission::checkAdminUser' );

?>