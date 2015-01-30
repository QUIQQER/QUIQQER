<?php

/**
 * is the site from another user marcated?
 *
 * @param String $project - Project data; JSON Array
 * @param String $id - Site ID
 * @return Array
 */
function ajax_site_isMarcateFromOther($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, $id );

    return $Site->isMarcateFromOther();
}

\QUI::$Ajax->register(
    'ajax_site_isMarcateFromOther',
    array( 'project', 'id' ),
    'Permission::checkAdminUser'
);
