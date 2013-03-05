<?php

/**
 * Return the plugin list
 *
 * @return array
 */
function ajax_system_plugins_list()
{
    $Plugins = QUI::getPlugins();
    $list    = $Plugins->getAvailablePlugins( true );

    $plugins = array();

    foreach ( $list as $Plugin ) {
        $plugins[] = $Plugin->getAttributes();
    }

    return $plugins;
}
QUI::$Ajax->register('ajax_system_plugins_list', false, 'Permission::checkSU');

?>