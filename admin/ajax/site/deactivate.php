<?php

/**
 * Deactivate a site
 *
 * @param string $project
 * @param string $id
 * @return bool
 */
function ajax_site_deactivate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit( $Project, (int)$id );

    $Site->deactivate();

    return !$Site->getAttribute('active') ? 0 : 1;
}

\QUI::$Ajax->register(
    'ajax_site_deactivate',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
