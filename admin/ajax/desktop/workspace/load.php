<?php

/**
 * Return current workspace
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_load',
    function () {
        $list = QUI\Workspace\Manager::getWorkspacesByUser(
            QUI::getUserBySession()
        );

        return $list;
    },
    false,
    'Permission::checkUser'
);
