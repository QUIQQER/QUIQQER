<?php

/**
 * Return all available panels
 *
 * @return array
 */

function ajax_desktop_workspace_getAvailablePanels()
{
    return \QUI\Workspace\Manager::getAvailablePanels();
}

\QUI::$Ajax->register(
    'ajax_desktop_workspace_getAvailablePanels',
    false,
    'Permission::checkUser'
);
