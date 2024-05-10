<?php

/**
 * Delete a permission
 *
 * @param $permission - permission
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_delete',
    static function ($permission) {
        QUI::getPermissionManager()->deletePermission($permission);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'permissions.message.delete.success'
            )
        );
    },
    ['permission'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
