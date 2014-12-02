<?php

/**
 * Add a new language link
 *
 * @param String $project
 * @param String $id
 * @param String $linkedParams - JSON Array
 *
 */
function ajax_site_language_add($project, $id, $linkedParams)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $linkedParams = json_decode( $linkedParams, true );

    $Site->addLanguageLink( $linkedParams['lang'], (int)$linkedParams['id'] );
}

\QUI::$Ajax->register(
    'ajax_site_language_add',
    array('project', 'id', 'linkedParams'),
    'Permission::checkAdminUser'
);
