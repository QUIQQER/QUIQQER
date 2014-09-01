<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
 */
function ajax_desktop_workspace_save($id, $data)
{
    $User = \QUI::getUserBySession();

    \QUI\System\Log::writeRecursive( $id );
    \QUI\System\Log::writeRecursive( $data );

    \QUI\Workspace\Manager::saveWorkspace($User, $id, array(
        'data' => $data
    ));
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_save',
    array( 'id', 'data' ),
    'Permission::checkUser'
);
