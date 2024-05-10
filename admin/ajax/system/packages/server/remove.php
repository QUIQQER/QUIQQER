<?php

/**
 * Remove a server
 *
 * @param string $server
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_remove',
    static function ($server) {
        QUI::getPackageManager()->removeServer($server);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/core',
                'message.packages.server.remove.successfully',
                ['server' => $server]
            )
        );
    },
    ['server'],
    [
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    ]
);
