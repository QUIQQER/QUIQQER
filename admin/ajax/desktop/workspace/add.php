<?php

/**
 * Return all widgets of a desktop
 *
 * @param string $data - workspace data, json array
 */
QUI::$Ajax->registerFunction(
    'ajax_desktop_workspace_add',
    function ($data) {
        $User = QUI::getUserBySession();
        $data = \json_decode($data, true);

        QUI\Workspace\Manager::addWorkspace(
            $User,
            $data['title'],
            $data['data'],
            $data['minHeight'],
            $data['minWidth']
        );
    },
    ['data'],
    'Permission::checkUser'
);
