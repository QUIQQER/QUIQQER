<?php

/**
 * Plugin deaktivieren
 *
 * @param string $plugin
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_activate',
    function ($plugin) {
        $Plugins = \QUI::getPlugins();
        $Plugin  = $Plugins->get($plugin);

        $Plugins->activate($Plugin);
        $Plugin->install();
    },
    ['plugin'],
    'Permission::checkSU'
);
