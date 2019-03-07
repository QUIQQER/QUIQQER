<?php

/**
 * Plugin deinstallieren und löschen
 *
 * @param string $plugin
 * @param string $params
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_delete',
    function ($plugin, $params) {
        $Plugins = QUI::getPlugins();
        $Plugin  = $Plugins->get($plugin);

        $Plugin->uninstall(
            json_decode($params, true)
        );
    },
    ['plugin', 'params'],
    'Permission::checkSU'
);
