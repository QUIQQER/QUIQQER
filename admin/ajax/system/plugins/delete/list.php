<?php

/**
 * Return the plugins which could be deleted
 *
 * @return array
 */
function ajax_system_plugins_delete_list()
{
    $Plugins = QUI::getPlugins();
    $list    = $Plugins->getInactivePlugins( true );
    $result  = array();

    foreach ( $list as $Plugin ) {
        $result[] = $Plugin->getAttributes();
    }

    return $result;
}
QUI::$Ajax->register('ajax_system_plugins_delete_list', false, 'Permission::checkSU');

?>