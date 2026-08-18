<?php

/**
 * Delete a permission
 *
 * @param $permission - permission
 */

QUI::getAjax()->registerFunction(
    'ajax_permissions_delete',
    static function ($permission): void {
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
