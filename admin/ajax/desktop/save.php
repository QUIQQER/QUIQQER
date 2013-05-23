<?php

/**
 * Saves the desktop for the user
 *
 * @param String $widgets - Desktop Widget Params
 */
function ajax_desktop_save($did, $widgets)
{
    $DesktopManager = \QUI::getDesktopManager();
    $Desktop        = $DesktopManager->get( $did );

    $widgets = json_decode( $widgets, true );

    $DesktopManager->save( $Desktop, $widgets );
}

\QUI::$Ajax->register(
    'ajax_desktop_save',
    array( 'did', 'widgets' ),
    'Permission::checkUser'
);

?>