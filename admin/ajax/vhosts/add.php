<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_vhosts_add($vhost)
{
    $VhostManager = new \QUI\System\VhostManager();
    $VhostManager->addVhost($vhost, array());
}

\QUI::$Ajax->register(
    'ajax_vhosts_add',
    array( 'vhost' ),
    'Permission::checkSU'
);
