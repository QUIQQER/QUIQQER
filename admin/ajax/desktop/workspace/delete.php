<?php

/**
 * Delete workspaces
 *
 * @param string $ids - Workspace IDs, json array
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_delete',
    function ($ids) {
        $User = QUI::getUserBySession();
        $ids  = \json_decode($ids, true);

        foreach ($ids as $id) {
            QUI\Workspace\Manager::deleteWorkspace($id, $User);
        }
    },
    ['ids'],
    'Permission::checkUser'
);
