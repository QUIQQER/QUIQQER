<?php

/**
 * Save the workspace
 *
 * @param integer $id - Workspace-ID
 * @param string $data - workspace data, json array
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_save',
    static function ($id, $data): void {
        $User = QUI::getUserBySession();

        QUI\Workspace\Manager::saveWorkspace($User, $id, [
            'data' => $data
        ]);
    },
    ['id', 'data'],
    'Permission::checkUser'
);
