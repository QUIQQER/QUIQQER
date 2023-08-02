<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 * @return false|string
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_session_hasPermission',
    function ($permission) {
        return QUI\Permissions\Permission::hasPermission($permission);
    },
    ['permission']
);
