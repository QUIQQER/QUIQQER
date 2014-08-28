<?php

/**
 * Return all widgets of a desktop
 *
 * @param Integer $did - Desktop-ID
 */
function ajax_desktop_workspace_load()
{
    return \QUI\Workspace\Manager::getListByUser(
        \QUI::getUserBySession()
    );
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_load',
    false,
    'Permission::checkUser'
);
