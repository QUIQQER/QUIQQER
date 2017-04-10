<?php

/**
 * Return the available toolbars for the user
 *
 * @param string / Integer $uid
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_editor_get_toolbarsFromUser',
    function ($uid, $assignedToolbars) {
        $User = QUI::getUsers()->get($uid);
        $User->setAttribute('assigned_toolbar', $assignedToolbars);

        return QUI\Editor\Manager::getToolbarsFromUser($User);
    },
    array('uid', 'assignedToolbars'),
    'Permission::checkAdminUser'
);
