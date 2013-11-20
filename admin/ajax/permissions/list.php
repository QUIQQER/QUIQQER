<?php

/**
 * Return the available permission list
 *
 * @return array
 */
function ajax_permissions_list()
{
    return \QUI::getRights()->getPermissionList();
}

QUI::$Ajax->register(
	'ajax_permissions_list',
    false,
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);

?>