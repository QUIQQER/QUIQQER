<?php

/**
 * Has the user the permission?
 *
 * @param string $permission - name of the permission
 * @return bool|int|array<array-key, mixed>|string
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_session_hasPermission',
    static function (string $permission): bool | int | array | string {
        return QUI\Permissions\Permission::hasPermission($permission);
    },
    ['permission']
);
