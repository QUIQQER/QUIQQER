<?php

/**
 * Return the uploaded system packages
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_upload_getList',
    static function (): array {
        return QUI\Package\LocalServer::getInstance()->getPackageList();
    },
    false,
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
