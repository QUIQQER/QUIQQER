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
        return \QUI\Rights\Permission::hasPermission($permission);
    },
    array('permission')
);
