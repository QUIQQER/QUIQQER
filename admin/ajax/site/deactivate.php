<?php

/**
 * Deactivate a site
 *
 * @param String $project
 * @param String $id
 */
function ajax_site_deactivate($project, $id)
{
    $Project = \QUI::getProjectManager()->decode( $project );
    $Site    = new \QUI\Projects\Site\Edit($Project, (int)$id);

    $Site->deactivate();
}

\QUI::$Ajax->register(
    'ajax_site_deactivate',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
