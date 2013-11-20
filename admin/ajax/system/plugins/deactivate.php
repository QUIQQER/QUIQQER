<?php

/**
 * Plugin deaktivieren
 *
 * @param unknown_type $plugin
 */
function ajax_system_plugins_deactivate($plugin)
{
    $Plugins = \QUI::getPlugins();

    $Plugins->deactivate(
        $Plugins->get($plugin)
    );
}
QUI::$Ajax->register('ajax_system_plugins_deactivate', array('plugin'), 'Permission::checkSU');

?>