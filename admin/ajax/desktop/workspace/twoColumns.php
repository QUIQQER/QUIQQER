<?php

/**
 * Return two column workspace default
 *
 * @return string
 */

QUI::getAjax()->registerFunction(
    'ajax_desktop_workspace_twoColumns',
    static function (): string {
        return QUI\Workspace\Manager::getTwoColumnDefault();
    },
    false,
    'Permission::checkUser'
);
