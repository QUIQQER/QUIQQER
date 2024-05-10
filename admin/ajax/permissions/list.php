<?php

/**
 * Return the available permission list
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_permissions_list',
    static fn(): array => QUI::getPermissionManager()->getPermissionList(),
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.permissions'
    ]
);
