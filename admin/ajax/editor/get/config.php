<?php

/**
 * Konfiguration bekommen welche zur Verfügung stehen
 *
 * @return Array
 */
function ajax_editor_get_config()
{
    return QUI_Editor_Manager::getConfig();
}
QUI::$Ajax->register('ajax_editor_get_config', false, 'Permission::checkSU');

?>