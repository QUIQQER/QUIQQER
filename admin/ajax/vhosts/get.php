<?php

/**
 * Return the vhost data
 *
 * @param String $vhost - vhost
 * @return Array
 */
function ajax_vhosts_get($vhost)
{
    $VhostManager = new \QUI\System\VhostManager();

    return $VhostManager->getVhost( $vhost );
}

\QUI::$Ajax->register(
    'ajax_vhosts_get',
    array( 'vhost' ),
    'Permission::checkSU'
);
