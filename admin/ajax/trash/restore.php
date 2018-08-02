<?php

/**
 * Restore sites
 *
 * @param string $project - Project data, JSON Array
 * @param string $ids - json array
 * @param string|integer $parentid - Site-ID
 */
QUI::$Ajax->registerFunction(
    'ajax_trash_restore',
    function ($project, $ids, $parentid) {
        $Project = QUI::getProjectManager()->decode($project);
        $ids     = json_decode($ids, true);
        $Trash   = $Project->getTrash();

        $Trash->restore($Project, $ids, $parentid);
    },
    ['project', 'ids', 'parentid'],
    'Permission::checkAdminUser'
);
