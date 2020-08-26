<?php

/**
 * Delete a permission
 *
 * @param $permission - permission
 */
QUI::$Ajax->registerFunction(
    'ajax_permissions_delete',
    function ($permission) {
        QUI::getPermissionManager()->deletePermission($permission);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/quiqqer',
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
