<?php

/**
 * Restore sites
 *
 * @param String $project - Project data, JSON Array
 * @param JSON Array $ids
 * @param String|Integer $parentid - Site-ID
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
