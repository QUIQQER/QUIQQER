<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param string $vhost
 * @param string $data
 * @return array
 */
function ajax_vhosts_save($vhost, $data)
{
    $data = json_decode($data, true);

    $VhostManager = new QUI\System\VhostManager();
    $VhostManager->editVhost($vhost, $data);
}

QUI::$Ajax->register(
    'ajax_vhosts_save',
    array('vhost', 'data'),
    'Permission::checkSU'
);
