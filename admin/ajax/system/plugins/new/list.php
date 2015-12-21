<?php

/**
 * Return the plugin list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_new_list',
    function () {
        $Update = new \QUI\Update();

        return $Update->getAvailablePlugins();
    },
    false,
    'Permission::checkSU'
);
