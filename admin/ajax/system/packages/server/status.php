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
    function ($server, $status) {
        QUI::getPackageManager()->setServerStatus($server, $status);
    },
    array('server', 'status'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
