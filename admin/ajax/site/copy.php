<?php

/**
 * Copy a site
 *
 * @param String $project
 * @param Integer $id
 * @param Integer $newParentId
 *
 * @return Integer - new site id
 */
function ajax_site_copy($project, $id, $newParentId)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $NewSite = $Site->copy( (int)$newParentId );

    return $NewSite->getId();
}

\QUI::$Ajax->register(
    'ajax_site_copy',
    array( 'project', 'id', 'newParentId' ),
    'Permission::checkAdminUser'
);
