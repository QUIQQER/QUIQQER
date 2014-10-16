<?php

/**
 * Edit a workspace
 *
 * @param Integer $id - Workspace ID
 * @param String $data - JSON Data Array; Workspace data
 */
function ajax_desktop_workspace_edit($id, $data)
{
    $User = \QUI::getUserBySession();
    $data = json_decode( $data, true );

    \QUI\Workspace\Manager::saveWorkspace( $User, $id, $data );
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_edit',
    array( 'id', 'data' ),
    'Permission::checkUser'
);
