<?php

/**
 * Return the plugin list
 *
 * @return array
 */
function ajax_system_plugins_new_list()
{
    $Update = new \QUI\Update();

    return $Update->getAvailablePlugins();
}
QUI::$Ajax->register('ajax_system_plugins_new_list', false, 'Permission::checkSU');

?>