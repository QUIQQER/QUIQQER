<?php

/**
 * Seite aktivieren
 *
 * @param String $id
 * @param String $lang
 * @param String $project
 */
function ajax_site_language_add($project, $lang, $id, $linkedParams)
{
    $linkedParams = json_decode( $linkedParams, true );

    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->addLanguageLink( $linkedParams['lang'], (int)$linkedParams['id'] );
}

\QUI::$Ajax->register(
    'ajax_site_language_add',
    array('project', 'lang', 'id', 'linkedParams'),
    'Permission::checkAdminUser'
);
