<?php

/**
 * Update Window Template für das CMS
 *
 * @return String
 * @deprecated
 */
function ajax_system_update_template($tpltype)
{
    $Engine  = QUI_Template::getEngine();
    $Plugins = QUI::getPlugins();

    if ( $tpltype == 'plugin' )
    {
        $Engine->assign(array(
            'plugins' => $Plugins->getAvailablePlugins(true)
        ));
    }

    $Engine->assign(array(
        'tpltype' => $tpltype
    ));

    return $Engine->fetch( CMS_DIR .'admin/ajax/system/update/template.html' );
}
QUI::$Ajax->register('ajax_system_update_template', array('tpltype'), 'Permission::checkSU');

?>