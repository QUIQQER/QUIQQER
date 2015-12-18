<?php

/**
 * Move a site under another site
 *
 * @param string $project - project data
 * @param integer $id - site ID
 * @param integer $newParentId - new parent ID
 *
 * @return array
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
