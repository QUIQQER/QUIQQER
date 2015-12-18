<?php

/**
 * Restore sites
 *
 * @param string $project - Project data, JSON Array
 * @param string $ids - json array
 * @param string|integer $parentid - Site-ID
 */
function ajax_trash_restore($project, $ids, $parentid)
{
    $Project = QUI::getProjectManager()->decode($project);
    $ids     = json_decode($ids, true);
    $Trash   = $Project->getTrash();

    $Trash->restore($Project, $ids, $parentid);
}

QUI::$Ajax->register(
    'ajax_trash_restore',
    array('project', 'ids', 'parentid'),
    'Permission::checkAdminUser'
);
