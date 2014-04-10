<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_vhosts_remove($vhost)
{
    $VhostManager = new \QUI\System\VhostManager();
    $VhostManager->removeVhost( $vhost );
}

\QUI::$Ajax->register(
    'ajax_vhosts_remove',
    array( 'vhost' ),
    'Permission::checkSU'
);
