<?php

/**
 * Return the available permission list
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_permissions_list',
    function () {
        return QUI::getPermissionManager()->getPermissionList();
    },
    false,
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    )
);
