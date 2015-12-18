<?php

/**
 * Create a linkage / shortcut
 *
 * @param string $project
 * @param integer $id
 * @param integer $newParentId
 *
 * @return array
 */
function ajax_site_linked($project, $id, $newParentId)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    $Site->linked((int)$newParentId);
}

QUI::$Ajax->register(
    'ajax_site_linked',
    array('project', 'id', 'newParentId'),
    'Permission::checkAdminUser'
);
