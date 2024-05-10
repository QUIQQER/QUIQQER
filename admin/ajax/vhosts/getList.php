<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_vhosts_getList',
    static function () {
        $VhostManager = new \QUI\System\VhostManager();

        return $VhostManager->getList();
    },
    false,
    'Permission::checkSU'
);
