<?php

/**
 * Return all installed packages
 *
 * @param string $params
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_list',
    function () {
        return QUI::getPackageManager()->getInstalled();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
