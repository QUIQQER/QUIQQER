<?php

/**
 * Set a status to a server
 *
 * @param string $server
 * @param string $status
 * @return array
 */
function ajax_system_packages_server_status($server, $status)
{
    QUI::getPackageManager()->setServerStatus($server, $status);
}

QUI::$Ajax->register(
    'ajax_system_packages_server_status',
    array('server', 'status'),
    array(
        'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);
