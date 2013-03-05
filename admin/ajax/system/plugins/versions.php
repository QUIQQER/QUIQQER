<?php

/**
 * Versionen anfragen, welche zur Verfügung stehen
 *
 * @param String $plugin - optional
 * @return Array
 */
function ajax_system_plugins_versions($plugin)
{
    $Update = new \QUI\Update();

    return $Update->getVersions( $plugin );
}
QUI::$Ajax->register('ajax_system_plugins_versions', array('plugin'), 'Permission::checkSU');

?>