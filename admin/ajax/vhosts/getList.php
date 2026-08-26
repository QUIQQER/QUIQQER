<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_vhosts_getList',
    static function (): array {
        $VhostManager = new \QUI\System\VhostManager();

        return $VhostManager->getList();
    },
    false,
    'Permission::checkSU'
);
