<?php

/**
 * Versionen anfragen, welche zur Verfügung stehen
 *
 * @param string $plugin - optional
 * @return array
 */
function ajax_system_plugins_versions($plugin)
{
    $Update = new \QUI\Update();

    return $Update->getVersions($plugin);
}

QUI::$Ajax->register('ajax_system_plugins_versions', array('plugin'), 'Permission::checkSU');
