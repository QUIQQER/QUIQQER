<?php

/**
 * Gibt die Buttons für den Benutzer zurück
 *
 * @param string|integer $gid
 * @return array
 */
function ajax_groups_panel_categories($gid)
{
    $Groups = QUI::getGroups();
    $Group  = $Groups->get((int)$gid);

    return QUI\Groups\Utils::getGroupToolbar($Group)->toArray();
}

QUI::$Ajax->register(
    'ajax_groups_panel_categories',
    array('gid'),
    'Permission::checkSU'
);
