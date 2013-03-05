<?php

/**
 * Desktop für den Benutzer speichern
 *
 * @param String $widgets - Desktop Widget Params
 */
function ajax_desktop_save($widgets)
{
    $User = QUI::getUserBySession();

    $User->setExtra('desktop', $widgets);
    $User->save();
}
QUI::$Ajax->register('ajax_desktop_save', array('widgets'), 'Permission::checkUser')


?>