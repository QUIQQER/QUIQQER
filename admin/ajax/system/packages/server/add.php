<?php

/**
 * Return all update servers
 *
 * @return Array
 */
function ajax_system_packages_server_add($server, $params)
{
    \QUI::getPackageManager()->addServer(
        $server,
        json_decode( $params, true )
    );

    \QUI::getMessagesHandler()->addSuccess(
        \QUI::getLocale()->get(
        	'quiqqer/system',
        	'message.packages.server.add.successfuly',
            array( 'server' => $server )
        )
    );
}

QUI::$Ajax->register(
	'ajax_system_packages_server_add',
    array( 'server', 'params' ),
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>