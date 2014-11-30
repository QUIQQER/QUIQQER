<?php

/**
 * Save the workspace
 *
 * @param integer $id - Workspace-ID
 * @param string $data - workspace data, json array
 */
function ajax_desktop_workspace_save($id, $data)
{
    $User = \QUI::getUserBySession();

    \QUI\Workspace\Manager::saveWorkspace($User, $id, array(
        'data' => $data
    ));
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_save',
    array( 'id', 'data' ),
    'Permission::checkUser'
);
