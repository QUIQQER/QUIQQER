<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @return array
 */
function ajax_vhosts_getList()
{
    $VhostManager = new \QUI\System\VhostManager();

    return $VhostManager->getList();
}

QUI::$Ajax->register(
    'ajax_vhosts_getList',
    false,
    'Permission::checkSU'
);
