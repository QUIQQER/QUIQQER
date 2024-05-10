<?php

/**
 * Edit a server entry
 *
 * @param string $server
 * @param string $params
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_edit',
    static function ($server, $params) {
        QUI::getPackageManager()->editServer(
            $server,
            json_decode($params, true)
        );
    },
    ['server', 'params'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
