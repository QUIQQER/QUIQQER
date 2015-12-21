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
    function ($server, $params) {
        QUI::getPackageManager()->addServer(
            $server,
            json_decode($params, true)
        );

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/system',
                'message.packages.server.add.successfuly',
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
