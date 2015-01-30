<?php

/**
 * Marcate a site
 *
 * @param String $project - Project data; JSON Array
 * @param String $id - Site ID
 * @return Array
 */
function ajax_site_marcate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, $id );

    $Site->marcate();
}

\QUI::$Ajax->register(
    'ajax_site_marcate',
    array( 'project', 'id' ),
    'Permission::checkAdminUser'
);
