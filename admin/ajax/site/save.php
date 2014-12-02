<?php

/**
 * Saves a site
 *
 * @param String $project - project data
 * @param Integer $id - Site ID
 * @param String $attributes - JSON Array
 * @return Array
 */
function ajax_site_save($project, $id, $attributes)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $attributes = json_decode( $attributes, true );

    $Site->setAttributes( $attributes );
    $Site->save();
    $Site->refresh();

    require_once 'get.php';

    return ajax_site_get( array( 'project' => $project ), $id );
}

\QUI::$Ajax->register(
    'ajax_site_save',
    array( 'project', 'id', 'attributes' ),
    'Permission::checkAdminUser'
);
