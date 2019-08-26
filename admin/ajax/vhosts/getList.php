<?php

/**
 * Gibt die Daten eines Benutzers zurück
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'ajax_vhosts_getList',
    function () {
        $VhostManager = new \QUI\System\VhostManager();
        return $VhostManager->getList();
    },
    false,
    'Permission::checkSU'
);
