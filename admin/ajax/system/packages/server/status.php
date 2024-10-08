<?php

/**
 * Set a status to a server
 *
 * @param string $server
 * @param string $status
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_status',
    static function ($server, $status): void {
        QUI::getPackageManager()->setServerStatus($server, $status);
    },
    ['server', 'status'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
