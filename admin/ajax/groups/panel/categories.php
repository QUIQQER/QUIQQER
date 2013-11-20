<?php

/**
 * Gibt die Buttons für den Benutzer zurück
 *
 * @param String / Integer $uid
 * @return Array
 */
function ajax_groups_panel_categories($gid)
{
    $Groups = \QUI::getGroups();
    $Group  = $Groups->get( (int)$gid );

    return \QUI\Groups\Utils::getGroupToolbar( $Group )->toArray();
}

\QUI::$Ajax->register(
    'ajax_groups_panel_categories',
    array('gid'),
    'Permission::checkSU'
);
