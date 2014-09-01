<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
 */
function ajax_desktop_workspace_add($data)
{
    $User = \QUI::getUserBySession();
    $data = json_decode( $data, true );

    \QUI\Workspace\Manager::addWorkspace(
        $User,
        $data['title'],
        $data['data'],
        $data['minHeight'],
        $data['minWidth']
    );
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_add',
    array( 'data' ),
    'Permission::checkUser'
);
