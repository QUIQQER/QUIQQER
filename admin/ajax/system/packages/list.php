<?php

/**
 * Return all installed packages
 *
 * @param string $params
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_system_packages_list',
    static function (): array {
        return QUI::getPackageManager()->getInstalled();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
