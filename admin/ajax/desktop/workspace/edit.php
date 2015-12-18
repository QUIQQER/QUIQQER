<?php

/**
 * Edit a workspace
 *
 * @param integer $id - Workspace ID
 * @param string $data - JSON Data Array; Workspace data
 */
function ajax_desktop_workspace_edit($id, $data)
{
    $User = QUI::getUserBySession();
    $data = json_decode($data, true);

    QUI\Workspace\Manager::saveWorkspace($User, $id, $data);
}

QUI::$Ajax->register(
    'ajax_desktop_workspace_edit',
    array('id', 'data'),
    'Permission::checkUser'
);
