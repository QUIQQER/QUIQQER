<?php

/**
 * reset the workspace
 */

QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_reset',
    static function (): void {
        QUI\Workspace\Manager::resetWorkspace(QUI::getUserBySession());
    },
    false,
    'Permission::checkUser'
);
