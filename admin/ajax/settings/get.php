<?php


function ajax_settings_get($file)
{
    $file = SYS_DIR . $file;

    if ( !file_exists( $file ) ) {
        return array();
    }

    $Config = Utils_Xml::getConfigFromXml( $file );

    if ( !$Config ) {
        return array();
    }

    return $Config->toArray();
}
QUI::$Ajax->register( 'ajax_settings_get', array( 'file' ), 'Permission::checkAdminUser' );

?>