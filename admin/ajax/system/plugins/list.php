<?php

/**
 * Return the plugin list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_list',
    function () {
        $Plugins = QUI::getPlugins();
        $list    = $Plugins->getAvailablePlugins(true);

        $plugins = [];

        foreach ($list as $Plugin) {
            $plugins[] = $Plugin->getAttributes();
        }

        return $plugins;
    },
    false,
    'Permission::checkSU'
);
