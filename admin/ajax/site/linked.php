<?php

/**
 * Seite speichern
 *
 * @param String $project
 * @param String $lang
 * @param Integer $id
 * @param JSON Array $attributes
 *
 * @return Array
 */
function ajax_site_linked($project, $lang, $id, $newParentId)
{
    $Project = \QUI::getProject( $project, $lang );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->linked( (int)$newParentId );
}

\QUI::$Ajax->register(
    'ajax_site_linked',
    array( 'project', 'lang', 'id', 'newParentId' ),
    'Permission::checkAdminUser'
);
