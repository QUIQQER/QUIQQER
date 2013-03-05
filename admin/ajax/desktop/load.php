<?php

/**
 * Desktop für den Benutzer bekommen
 *
 * @param String $widgets - Desktop Widget Params
 */
function ajax_desktop_load()
{
    $User = QUI::getUserBySession();

    return $User->getExtra('desktop');
}
QUI::$Ajax->register('ajax_desktop_load', array('widgets'), 'Permission::checkUser')

?>