<?php

/**
 * Return all update servers
 *
 * @return Array
 */
function ajax_system_packages_server_remove($server)
{
    QUI::getPackageManager()->removeServer(
        json_decode($server, true)
    );

    QUI::getMessagesHandler()->addSuccess(
        QUI::getLocale()->get(
        	'quiqqer/system',
        	'message.packages.server.remove.successfuly',
            array( 'server' => $server )
        )
    );
}

QUI::$Ajax->register(
	'ajax_system_packages_server_remove',
    array( 'server', 'params' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>