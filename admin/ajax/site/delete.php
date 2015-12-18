<?php

/**
 * Delete a site
 *
 * @param string $project
 * @param string $id
 * @return boolean
 */
function ajax_site_delete($project, $id)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    return $Site->delete();
}

QUI::$Ajax->register(
    'ajax_site_delete',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
