<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param String / Integer $uid
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
