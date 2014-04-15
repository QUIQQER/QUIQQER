<?php

/**
 * Seite aktivieren
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_site_language_remove($project, $lang, $id, $linkedParams)
{
    $linkedParams = json_decode( $linkedParams, true );

    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->removeLanguageLink( $linkedParams['lang'] );
}

\QUI::$Ajax->register(
    'ajax_site_language_remove',
    array('project', 'lang', 'id', 'linkedParams'),
    'Permission::checkAdminUser'
);
