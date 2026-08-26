<?php

/**
 * Gibt die Buttons für den Benutzer zurück
 *
 * @param string|integer $gid
 * @return array
 */

QUI::getAjax()->registerFunction(
    'ajax_groups_panel_categories',
    static function ($gid): array {
        $Groups = QUI::getGroups();
        $Group = $Groups->get($gid);

        return QUI\Groups\Utils::getGroupToolbar($Group)->toArray();
    },
    ['gid'],
    'Permission::checkSU'
);
