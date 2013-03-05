<?php

/**
 * Toolbars bekommen welche zur Verfügung stehen
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_editor_get_toolbars()
{
    return QUI_Editor_Manager::getToolbars();
}
QUI::$Ajax->register('ajax_editor_get_toolbars', false, 'Permission::checkSU');

?>