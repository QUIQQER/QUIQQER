<?php

/**
 * Set the standard workspace
 *
 * @param Integer $id - workspace id
 */
function ajax_desktop_workspace_setStandard($id)
{
    \QUI\Workspace\Manager::setStandardWorkspace(
        \QUI::getUserBySession(),
        $id
    );
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_setStandard',
    array( 'id' ),
    'Permission::checkUser'
);
