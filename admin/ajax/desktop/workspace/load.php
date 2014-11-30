<?php

/**
 * Return current workspace
 */
function ajax_desktop_workspace_load()
{
    $list = \QUI\Workspace\Manager::getWorkspacesByUser(
        \QUI::getUserBySession()
    );

    return $list;
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_load',
    false,
    'Permission::checkUser'
);
