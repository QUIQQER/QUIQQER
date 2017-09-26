<?php

/**
 * Return two column workspace default
 *
 * @return string
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_twoColumns',
    function () {
        return QUI\Workspace\Manager::getTwoColumnDefault();
    },
    false,
    'Permission::checkUser'
);
