<?php

/**
 * Create a linkage / shortcut
 *
 * @param String $project
 * @param Integer $id
 * @param Integer $newParentId
 *
 * @return Array
 */
function ajax_site_linked($project, $id, $newParentId)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->linked( (int)$newParentId );
}

\QUI::$Ajax->register(
    'ajax_site_linked',
    array( 'project', 'id', 'newParentId' ),
    'Permission::checkAdminUser'
);
