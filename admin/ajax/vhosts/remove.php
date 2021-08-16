<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @param string $vhost
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_vhosts_remove',
    function ($vhost) {
        $VhostManager = new QUI\System\VhostManager();
        $VhostManager->removeVhost($vhost);
    },
    ['vhost'],
    'Permission::checkSU'
);
