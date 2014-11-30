<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
 * @deprecated
 */
function ajax_desktop_load($did)
{
    $DesktopManager = \QUI::getDesktopManager();
    $Desktop        = $DesktopManager->get( $did );

    return $Desktop->getWidgetList();
}

\QUI::$Ajax->register(
    'ajax_desktop_load',
    array('did'),
    'Permission::checkUser'
);
