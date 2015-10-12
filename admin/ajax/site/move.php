<?php

/**
 * Move a site under another site
 *
 * @param String $project - project data
 * @param Integer $id - site ID
 * @param Integer $newParentId - new parent ID
 *
 * @return Array
 */
function ajax_site_move($project, $id, $newParentId)
{
    $Project = QUI::getProjectManager()->decode($project);
    $Site    = new QUI\Projects\Site\Edit($Project, (int)$id);

    $Site->move((int)$newParentId);
}

QUI::$Ajax->register(
    'ajax_site_move',
    array('project', 'id', 'newParentId'),
    'Permission::checkAdminUser'
);
