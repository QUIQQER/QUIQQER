<?php

/**
 * Save the workspace
 *
 * @param integer $id - Workspace-ID
 * @param string $data - workspace data, json array
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_save',
    function ($id, $data) {
        $User = QUI::getUserBySession();

        QUI\Workspace\Manager::saveWorkspace($User, $id, array(
            'data' => $data
        ));
    },
    array('id', 'data'),
    'Permission::checkUser'
);
