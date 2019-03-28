<?php

/**
 * Edit a workspace
 *
 * @param integer $id - Workspace ID
 * @param string $data - JSON Data Array; Workspace data
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_edit',
    function ($id, $data) {
        $User = QUI::getUserBySession();
        $data = \json_decode($data, true);

        QUI\Workspace\Manager::saveWorkspace($User, $id, $data);
    },
    ['id', 'data'],
    'Permission::checkUser'
);
