<?php

/**
 * Activate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */
function ajax_site_activate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->activate();

    return $Site->getAttribute( 'active' );
}

\QUI::$Ajax->register(
    'ajax_site_activate',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
