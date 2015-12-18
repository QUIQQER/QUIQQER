<?php

/**
 * Destroy site ids
 *
 * @param string $project - Project data, JSON Array
 * @param string $ids - JSON Array, List of site ids
 */
function ajax_trash_destroy($project, $ids)
{
    $Project = QUI::getProjectManager()->decode($project);
    $ids     = json_decode($ids, true);
    $Trash   = $Project->getTrash();

    $Trash->destroy($Project, $ids);
}

QUI::$Ajax->register(
    'ajax_trash_destroy',
    array('project', 'ids'),
    'Permission::checkAdminUser'
);
