<?php

/**
 * Add a permission
 *
 * @param $permission - binded object id
 * @param $permissiontype - binded object type
 * @param string $area
 * @return boolean
 *
 * @throws QUI\Exception
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_add',
    static function ($permission, $permissiontype, $area): bool {
        $Manager = QUI::getPermissionManager();
        $permissions = $Manager->getPermissionList();

        if (isset($permissions[$permission])) {
            throw new QUI\Exception(
                QUI::getLocale()->get(
                    'quiqqer/core',
                    'exception.permissions.exists'
                )
            );
        }

        $Manager->addPermission([
            'name' => $permission,
            'title' => $permission,
            'desc' => $permission,
            'type' => $permissiontype,
            'area' => $area,
            'src' => 'user'
        ]);

        return true;
    },
    ['permission', 'permissiontype', 'area'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
