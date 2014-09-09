<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
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
