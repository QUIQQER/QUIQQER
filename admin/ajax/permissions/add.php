<?php

/**
 * Add a permission
 *
 * @param $permission     - binded object id
 * @param $permissiontype - binded object type
 */
function ajax_permissions_add($permission, $permissiontype, $area)
{
    $Manager     = \QUI::getPermissionManager();
    $permissions = $Manager->getPermissionList();

    if ( isset( $permissions[ $permission ] ) )
    {
        throw new QException(
            \QUI::getLocale()->get(
                'quiqqer/system',
                'exception.permissions.exists'
            )
        );
    }

    $Manager->addPermission(array(
        'name'  => $permission,
        'title' => $permission,
        'desc'  => $permission,
        'type'  => $permissiontype,
        'area'  => $area,
        'src'   => 'user'
    ));

    return true;
}

QUI::$Ajax->register(
	'ajax_permissions_add',
    array( 'permission', 'permissiontype', 'area' ),
    array(
    	'Permission::checkAdminUser',
    	'quiqqer.system.permissions'
    )
);

?>