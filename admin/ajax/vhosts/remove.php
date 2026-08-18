<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param string $vhost
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_vhosts_remove',
    static function ($vhost): void {
        $VhostManager = new QUI\System\VhostManager();
        $VhostManager->removeVhost($vhost);
    },
    ['vhost'],
    'Permission::checkSU'
);
