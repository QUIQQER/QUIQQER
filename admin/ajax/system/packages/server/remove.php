<?php

/**
 * Remove a server
 *
 * @param string $server
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_system_packages_server_remove',
    function ($server) {
        QUI::getPackageManager()->removeServer($server);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.packages.server.remove.successfuly',
                array('server' => $server)
            )
        );
    },
    array('server', 'params'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
