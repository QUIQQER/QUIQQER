<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param string $vhost
 * @param string $data
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_vhosts_save',
    static function ($vhost, $data): void {
        $data = json_decode($data, true);

        $VhostManager = new QUI\System\VhostManager();
        $VhostManager->editVhost($vhost, $data);
    },
    ['vhost', 'data'],
    'Permission::checkSU'
);
