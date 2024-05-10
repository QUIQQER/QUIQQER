<?php

/**
 * Set the standard workspace
 *
 * @param integer $id - Workspace-ID
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_setStandard',
    static function ($id): void {
        QUI\Workspace\Manager::setStandardWorkspace(
            QUI::getUserBySession(),
            $id
        );
    },
    ['id'],
    'Permission::checkUser'
);
