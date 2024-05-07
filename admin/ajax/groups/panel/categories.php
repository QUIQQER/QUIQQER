<?php

/**
 * Gibt die Buttons für den Benutzer zurück
 *
 * @param string|integer $gid
 * @return array
 */

QUI::$Ajax->registerFunction(
    'ajax_groups_panel_categories',
    function ($gid) {
        $Groups = QUI::getGroups();
        $Group = $Groups->get($gid);

        return QUI\Groups\Utils::getGroupToolbar($Group)->toArray();
    },
    ['gid'],
    'Permission::checkSU'
);
