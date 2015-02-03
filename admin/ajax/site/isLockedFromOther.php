<?php

/**
 * is the site from another user locked?
 *
 * @param String $project - Project data; JSON Array
 * @param String $id - Site ID
 * @return Array
 */
function ajax_site_isLockedFromOther($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, $id );

    return $Site->isLockedFromOther();
}

\QUI::$Ajax->register(
    'ajax_site_isLockedFromOther',
    array( 'project', 'id' ),
    'Permission::checkAdminUser'
);
