<?php

/**
 * Has the user the permission?
 *
 * @param $permission - name of the permission
 */
function ajax_permissions_session_hasPermission($permission)
{
    return \QUI\Rights\Permission::hasPermission( $permission );
}

QUI::$Ajax->register(
    'ajax_permissions_session_hasPermission',
    array( 'permission' )
);
