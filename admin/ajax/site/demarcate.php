<?php

/**
 * Marcate a site
 *
 * @param String $project - Project data; JSON Array
 * @param String $id - Site ID
 * @return Array
 */
function ajax_site_demarcate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, $id );

    $Site->demarcate();
}

\QUI::$Ajax->register(
    'ajax_site_demarcate',
    array( 'project', 'id' ),
    'Permission::checkAdminUser'
);
