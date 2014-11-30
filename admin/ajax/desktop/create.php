<?php

/**
 * Create a Desktop for the user
 *
 * @param String $title - Desktop title
 * @deprecated
 */
function ajax_desktop_create($title)
{
    $DesktopManager = \QUI::getDesktopManager();
    $Desktop        = $DesktopManager->create( $title );

    return $Desktop->getId();
}

\QUI::$Ajax->register(
    'ajax_desktop_create',
    array( 'title' ),
    'Permission::checkUser'
);
