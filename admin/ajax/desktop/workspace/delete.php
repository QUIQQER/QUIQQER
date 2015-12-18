<?php

/**
 * Delete workspaces
 *
 * @param string $ids - Workspace IDs, json array
 */
function ajax_desktop_workspace_delete($ids)
{
    $User = QUI::getUserBySession();
    $ids  = json_decode($ids, true);

    foreach ($ids as $id) {
        QUI\Workspace\Manager::deleteWorkspace($id, $User);
    }
}

QUI::$Ajax->register(
    'ajax_desktop_workspace_delete',
    array('ids'),
    'Permission::checkUser'
);
