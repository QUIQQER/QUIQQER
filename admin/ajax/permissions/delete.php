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
                'quiqqer/system',
                'permissions.message.delete.success'
            )
        );
    },
    array('permission'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);
