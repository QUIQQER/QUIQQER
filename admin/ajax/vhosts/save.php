<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param string $vhost
 * @param string $data
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_vhosts_save',
    function ($vhost, $data) {
        $data = \json_decode($data, true);

        $VhostManager = new QUI\System\VhostManager();
        $VhostManager->editVhost($vhost, $data);
    },
    ['vhost', 'data'],
    'Permission::checkSU'
);
