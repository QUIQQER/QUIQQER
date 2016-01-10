<?php

/**
 * Plugin deaktivieren
 *
 * @param string $plugin
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_deactivate',
    function ($plugin) {
        $Plugins = QUI::getPlugins();

        $Plugins->deactivate(
            $Plugins->get($plugin)
        );
    },
    array('plugin'),
    'Permission::checkSU'
);
