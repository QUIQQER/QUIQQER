<?php

/**
 * Delete a permission
 *
 * @param $permission - permission
 */
function ajax_permissions_delete($permission)
{
    \QUI::getPermissionManager()->deletePermission( $permission );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
            'quiqqer/system',
            'permissions.message.delete.success'
        )
    );
}

QUI::$Ajax->register(
    'ajax_permissions_delete',
    array( 'permission' ),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);
