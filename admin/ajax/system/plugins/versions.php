<?php

/**
 * Versionen anfragen, welche zur Verfügung stehen
 *
 * @param string $plugin - optional
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_versions',
    function ($plugin) {
        $Update = new \QUI\Update();

        return $Update->getVersions($plugin);
    },
    ['plugin'],
    'Permission::checkSU'
);
