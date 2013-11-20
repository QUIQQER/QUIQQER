<?php

/**
 * Plugin deaktivieren
 *
 * @param unknown_type $plugin
 */
function ajax_system_plugins_activate($plugin)
{
    $Plugins = \QUI::getPlugins();
    $Plugin  = $Plugins->get($plugin);

    $Plugins->activate( $Plugin );
    $Plugin->install();
}
QUI::$Ajax->register('ajax_system_plugins_activate', array('plugin'), 'Permission::checkSU');

?>