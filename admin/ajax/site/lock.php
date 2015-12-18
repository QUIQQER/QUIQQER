<?php

/**
 * Lock a site
 *
 * @param string $project - Project data; JSON Array
 * @param string $id - Site ID
 * @return array
 */
function ajax_site_lock($project, $id)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, $id);

    $Site->lock();
}

QUI::$Ajax->register(
    'ajax_site_lock',
    array('project', 'id'),
    'Permission::checkAdminUser'
);
