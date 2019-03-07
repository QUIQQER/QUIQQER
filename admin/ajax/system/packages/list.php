<?php

/**
 * Return all installed packages
 *
 * @param string $params
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_list',
    function ($params) {
        return QUI::getPackageManager()->getInstalled(
            json_decode($params, true)
        );
    },
    ['params'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
