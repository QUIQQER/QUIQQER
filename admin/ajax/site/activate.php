<?php

/**
 * Activate a site
 *
 * @param String $project
 * @param String $id
 */
function ajax_site_activate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->activate();
}

\QUI::$Ajax->register(
    'ajax_site_activate',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
