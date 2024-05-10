<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 * @return false|string
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_session_hasPermission',
    static fn($permission): \QUI\Permissions\Permission|bool|string => QUI\Permissions\Permission::hasPermission($permission),
    ['permission']
);
