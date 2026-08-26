<?php

/**
 * Return all available panels
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_desktop_workspace_getAvailablePanels',
    static function (): array {
        return QUI\Workspace\Manager::getAvailablePanels();
    },
    false,
    'Permission::checkUser'
);
