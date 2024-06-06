<?php

/**
 * Return the available permission list
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_list',
    static function (): array {
        return QUI::getPermissionManager()->getPermissionList();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
