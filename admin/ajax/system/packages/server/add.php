<?php

/**
 * Return all update servers
 *
 * @param string $server
 * @param string $params
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_add',
    static function ($server, $params): void {
        QUI::getPackageManager()->addServer(
            $server,
            json_decode($params, true)
        );

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.packages.server.add.successfully',
                ['server' => $server]
            )
        );
    },
    ['server', 'params'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
