<?php

/**
 * Return all update servers
 *
 * @return Array
 */
function ajax_system_packages_server_list()
{
    $list = \QUI::getPackageManager()->getServerList();
    $data = array();

    foreach ( $list as $server => $params )
    {
        $active = 0;
        $type   = '';

        if ( isset( $params['active'] ) ) {
            $active = (int)$params['active'];
        }

        if ( isset( $params['type'] ) ) {
            $type = $params['type'];
        }

        $data[] = array(
            'server' => $server,
            'type'   => $type,
            'active' => $active
        );
    }

    return $data;
}

QUI::$Ajax->register(
	'ajax_system_packages_server_list',
    false,
    array(
    	'Permission::checkAdminUser',
        'quiqqer.system.update'
    )
);

?>