<?php

/**
 * Return the plugins which could be deleted
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_plugins_delete_list',
    function () {
        $Plugins = QUI::getPlugins();
        $list    = $Plugins->getInactivePlugins(true);
        $result  = [];

        foreach ($list as $Plugin) {
            $result[] = $Plugin->getAttributes();
        }

        return $result;
    },
    false,
    'Permission::checkSU'
);
