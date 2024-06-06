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
    static function ($uid, $assignedToolbars): array {
        $User = QUI::getUsers()->get($uid);

        if (!empty($assignedToolbars)) {
            $User->setAttribute('assigned_toolbar', $assignedToolbars);
        }

        return QUI\Editor\Manager::getToolbarsFromUser($User);
    },
    ['uid', 'assignedToolbars'],
    'Permission::checkAdminUser'
);
