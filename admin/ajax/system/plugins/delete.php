<?php

/**
 * Plugin deinstallieren und löschen
 *
 * @param string $plugin
 * @param string $params
 */
function ajax_system_plugins_delete($plugin, $params)
{
    $Plugins = QUI::getPlugins();
    $Plugin  = $Plugins->get($plugin);

    $Plugin->uninstall(
        json_decode($params, true)
    );
}

QUI::$Ajax->register('ajax_system_plugins_delete', array('plugin', 'params'), 'Permission::checkSU');
