<?php

/**
 * Seite aktivieren
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_site_activate($project, $lang, $id)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \Projects_Site_Edit( $Project, (int)$id );

    return $Site->activate();
}
QUI::$Ajax->register('ajax_site_activate', array('project', 'lang', 'id'), 'Permission::checkAdminUser');

?>