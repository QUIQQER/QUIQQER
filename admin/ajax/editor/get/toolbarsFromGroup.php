<?php

/**
 * Return the available toolbars for the user
 *
 * @param string / Integer $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_editor_get_toolbarsFromGroup',
    function ($gid, $assignedToolbars) {
        $Group = QUI::getGroups()->get($gid);
        $Group->setAttribute('assigned_toolbar', $assignedToolbars);

        return QUI\Editor\Manager::getToolbarsFromGroup($Group);
    },
    ['uid', 'assignedToolbars'],
    'Permission::checkAdminUser'
);
